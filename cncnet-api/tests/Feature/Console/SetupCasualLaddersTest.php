<?php

namespace Tests\Feature\Console;

use App\Models\Ladder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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

        config(['qm.casual_tunnels' => []]);
        Http::preventStrayRequests();

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

    public function test_ladder_rules_reach_the_spawn_ini(): void
    {
        $response = $this->queuePlayers('yr-casual-1v1', 2);

        $this->assertEquals('10000', $response->json('spawn.Settings.Credits'));
        $this->assertEquals('Yes', $response->json('spawn.Settings.MCVRedeploy'));
        $this->assertEquals('Yes', $response->json('spawn.Settings.BridgeDestroy'));
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
        for ($multiIndex = 1; $multiIndex <= $playerCount; $multiIndex++)
        {
            $alliances = $response->json("spawn.Multi{$multiIndex}_Alliances") ?? [];

            $this->assertCount($teamSize - 1, $alliances, "Multi{$multiIndex}");
            $this->assertNotContains($multiIndex - 1, $alliances, "Multi{$multiIndex} allied with itself");
        }
    }
}
