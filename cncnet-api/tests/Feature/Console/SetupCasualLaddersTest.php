<?php

namespace Tests\Feature\Console;

use App\Models\Ladder;
use App\Models\QmMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SetupCasualLaddersTest extends TestCase
{
    use RefreshDatabase;

    private array $matchRequest = [
        'version' => '1.83',
        'type' => 'match me up',
        'side' => 1,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('qm:setup-casual-ladders')->assertSuccessful();
    }

    public function test_creates_casual_ladders(): void
    {
        $this->assertEquals(
            ['yr-casual-1v1', 'yr-casual-2v2', 'yr-casual-3v3', 'yr-casual-4v4'],
            Ladder::where('is_casual', true)->orderBy('id')->pluck('abbreviation')->all()
        );

        // Running the command again updates the existing ladders instead of duplicating them
        $this->artisan('qm:setup-casual-ladders')->assertSuccessful();
        $this->assertEquals(4, Ladder::where('is_casual', true)->count());
    }

    public function test_ladders_and_queue_counts_check_back_at_different_moments(): void
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $checkbacks = [];
        foreach (['yr-casual-1v1', 'yr-casual-2v2', 'yr-casual-3v3', 'yr-casual-4v4'] as $i => $ladder)
        {
            $response = $this->postJson("/api/v1/qm/casual/{$ladder}/Newcomer{$i}", $this->matchRequest + ['search_token' => md5("Newcomer{$i}")]);
            $checkbacks[$ladder] = $response->json('checkback_ms');
        }
        $checkbacks['queue counts'] = $this->getJson('/api/v1/qm/casual/queue-counts')->json('checkback_ms');

        // The four ladders and the queue counts are spread evenly over the checkback interval
        $moments = array_map(fn($checkbackMs) => ($now->getTimestampMs() + $checkbackMs) % 5000, $checkbacks);
        $this->assertEqualsCanonicalizing([0, 1000, 2000, 3000, 4000], array_values($moments), json_encode($moments));
    }

    public function test_ladder_rules_reach_the_spawn_ini(): void
    {
        $response = $this->queuePlayers('yr-casual-1v1', 2);

        $this->assertEquals('10000', $response->json('spawn.Settings.Credits'));
        $this->assertEquals('Yes', $response->json('spawn.Settings.MCVRedeploy'));
        $this->assertEquals('Yes', $response->json('spawn.Settings.BridgeDestroy'));
    }

    public function test_1v1_players_play_allied_against_soviet(): void
    {
        $response = $this->queuePlayers('yr-casual-1v1', 2);

        $this->assertBalancedCountries($response, 2);
    }

    public function test_1v1_players_are_not_allied(): void
    {
        $response = $this->queuePlayers('yr-casual-1v1', 2);

        $this->assertNull($response->json('spawn.Multi1_Alliances'));
        $this->assertNull($response->json('spawn.Multi2_Alliances'));
    }

    public function test_only_countries_allowed_on_the_map_are_picked(): void
    {
        QmMap::where('ladder_id', Ladder::where('abbreviation', 'yr-casual-1v1')->value('id'))->update(['allowed_sides' => '-1,2,7']);

        $response = $this->queuePlayers('yr-casual-1v1', 2);

        $this->assertEqualsCanonicalizing([2, 7], $this->getCountries($response));
    }

    public function test_players_keep_their_side_on_games_without_factions(): void
    {
        config(['qm.casual_factions' => []]);

        $response = $this->queuePlayers('yr-casual-1v1', 2);

        $this->assertEquals([1, 1], $this->getCountries($response));
    }

    public function test_2v2_players_are_matched_into_two_allied_teams(): void
    {
        $this->assertTeamMatch('yr-casual-2v2', 4);
    }

    public function test_3v3_players_are_matched_into_two_allied_teams(): void
    {
        $this->assertTeamMatch('yr-casual-3v3', 6);
    }

    public function test_4v4_players_are_matched_into_two_allied_teams(): void
    {
        $this->assertTeamMatch('yr-casual-4v4', 8);
    }

    /**
     * Queues the given number of new players and returns the response of the last one.
     */
    private function queuePlayers(string $ladder, int $playerCount)
    {
        $start = Carbon::now();

        for ($i = 1; $i <= $playerCount; $i++)
        {
            Carbon::setTestNow($start->clone()->addSeconds($i));
            $response = $this->postJson("/api/v1/qm/casual/{$ladder}/Newcomer{$i}", $this->matchRequest + ['search_token' => md5("Newcomer{$i}")]);

            $expectedType = $i < $playerCount ? 'please wait' : 'spawn';
            $this->assertEquals($expectedType, $response->json('type'), "Newcomer{$i}: " . json_encode($response->json()));
        }

        return $response;
    }

    private function assertTeamMatch(string $ladder, int $playerCount): void
    {
        $response = $this->queuePlayers($ladder, $playerCount);

        $this->assertEquals($playerCount, $response->json('spawn.Settings.PlayerCount'));

        // Every player is allied with all teammates and with no one else
        $teamSize = $playerCount / 2;
        $teams = [];
        for ($multiIndex = 1; $multiIndex <= $playerCount; $multiIndex++)
        {
            $alliances = $response->json("spawn.Multi{$multiIndex}_Alliances") ?? [];

            $this->assertCount($teamSize - 1, $alliances, "Multi{$multiIndex}");
            $this->assertNotContains($multiIndex - 1, $alliances, "Multi{$multiIndex} allied with itself");

            $team = array_merge([$multiIndex - 1], array_values($alliances));
            sort($team);
            $teams[$multiIndex - 1] = $team;
        }

        foreach ($teams as $color => $team)
        {
            foreach ($team as $teammate)
            {
                $this->assertEquals($team, $teams[$teammate], "Player {$color} and player {$teammate} are not in the same team");
            }
        }

        $this->assertBalancedCountries($response, $playerCount);
    }

    /**
     * Half of the players play Allied and the other half Soviet countries.
     */
    private function assertBalancedCountries($response, int $playerCount): void
    {
        $countries = $this->getCountries($response);
        [$alliedCountries, $sovietCountries] = config('qm.casual_factions')['yr'];

        $this->assertCount($playerCount, $countries);
        $this->assertCount($playerCount / 2, array_intersect($countries, $alliedCountries), json_encode($countries));
        $this->assertCount($playerCount / 2, array_intersect($countries, $sovietCountries), json_encode($countries));
    }

    private function getCountries($response): array
    {
        return array_map('intval', array_values($response->json('spawn.HouseCountries')));
    }
}
