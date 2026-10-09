<?php

namespace Tests\Feature\Api\Qm\MatchRequest;

use App\Models\Player;
use App\Models\QmCanceledMatch;
use App\Models\QmMatch;
use App\Models\QmMatchPlayer;
use App\Models\QmQueueEntry;
use App\Models\QmUserId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Api\Auth\JwtAuthHelper;
use Tests\TestCase;

class CasualMatchRequestTest extends TestCase
{
    use RefreshDatabase;
    use QmPlayerHelper;
    use JwtAuthHelper;

    private $ladder;
    private $rankedLadder;
    private Carbon $now;

    private array $matchRequest = [
        'version' => '1.83',
        'type' => 'match me up',
        'side' => 1,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Ladder histories are created for the month of the current (test) time
        $this->now = Carbon::create(2026, 4, 20, 10, 10, 0);
        Carbon::setTestNow($this->now);

        $this->ladder = $this->makeLadder(2);
        $this->ladder->is_casual = true;
        $this->ladder->save();
        $this->makeLadderHistory($this->ladder);

        // makeLadder() always uses the abbreviation "tl"
        $this->rankedLadder = $this->makeLadder(2);
        $this->rankedLadder->abbreviation = 'tl-ranked';
        $this->rankedLadder->save();
        $this->makeLadderHistory($this->rankedLadder);

        // Tests must not contact the real tunnel servers; by default no tunnel is configured
        config(['qm.casual_tunnels' => []]);
        Http::preventStrayRequests();
    }

    private function casualRequest(string $playerName, array $data = [], ?string $ladder = null)
    {
        return $this->postJson(
            '/api/v1/qm/casual/' . ($ladder ?? $this->ladder->abbreviation) . '/' . rawurlencode($playerName),
            $data + ['search_token' => self::searchToken($playerName)] + $this->matchRequest
        );
    }

    /**
     * Every test client uses its own search token for a player name.
     */
    private static function searchToken(string $playerName): string
    {
        return md5('search of ' . $playerName);
    }

    private function waitSeconds(int $seconds): void
    {
        $this->now = $this->now->clone()->addSeconds($seconds);
        Carbon::setTestNow($this->now);
    }

    public function test_players_are_matched_without_accounts(): void
    {
        $first = $this->casualRequest('Newcomer1');
        $this->assertEquals('please wait', $first->json('type'), json_encode($first->json()));
        $this->assertEquals(5, $first->json('checkback'));

        $this->waitSeconds(5);
        $second = $this->casualRequest('Newcomer2');

        $this->assertEquals('spawn', $second->json('type'), json_encode($second->json()));
        $this->assertEquals(2, $second->json('spawn.Settings.PlayerCount'));
        $this->assertNotNull($second->json('spawn.HouseCountries'));
        $this->assertEquals('Newcomer1', $second->json('spawn.Other1.Name'));
        $this->assertStringEndsWith('@casual.invalid', Player::where('username', 'Newcomer1')->first()->user->email);
    }

    public function test_first_player_receives_spawn_on_next_checkback(): void
    {
        $this->casualRequest('Newcomer1');
        $this->waitSeconds(5);
        $this->casualRequest('Newcomer2');

        $this->waitSeconds(5);
        $response = $this->casualRequest('Newcomer1');

        $this->assertEquals('spawn', $response->json('type'), json_encode($response->json()));
        $this->assertEquals('Newcomer2', $response->json('spawn.Other1.Name'));
    }

    public function test_match_is_relayed_through_allocated_tunnel(): void
    {
        config(['qm.casual_tunnels' => [['ip' => '203.0.113.10', 'port' => 50000, 'name' => 'Test Tunnel']]]);
        Http::fake(['203.0.113.10:50000/*' => Http::response('[1234,-5678]', 200)]);

        $this->casualRequest('Newcomer1');
        $this->waitSeconds(5);
        $response = $this->casualRequest('Newcomer2');

        $this->assertEquals('spawn', $response->json('type'), json_encode($response->json()));
        $this->assertEquals('203.0.113.10', $response->json('spawn.Tunnel.Ip'));
        $this->assertEquals(50000, $response->json('spawn.Tunnel.Port'));
        $this->assertEquals('0.0.0.0', $response->json('spawn.Other1.Ip'));
        $this->assertContains($response->json('spawn.Settings.Port'), [1234, 59858]);
    }

    public function test_no_match_is_made_while_no_tunnel_is_available(): void
    {
        config(['qm.casual_tunnels' => [['ip' => '203.0.113.10', 'port' => 50000, 'name' => 'Test Tunnel']]]);
        Http::fake(['203.0.113.10:50000/*' => Http::sequence()->push('', 500)->push('[1234,-5678]', 200)]);

        $this->casualRequest('Newcomer1');
        $this->waitSeconds(5);
        $response = $this->casualRequest('Newcomer2');

        $this->assertEquals('please wait', $response->json('type'), json_encode($response->json()));
        $this->assertEquals(0, QmMatch::count());

        // The tunnel is not asked again right away
        $this->waitSeconds(5);
        $this->casualRequest('Newcomer1');
        Http::assertSentCount(1);

        // Later the tunnel works again and the players are matched through it
        $this->waitSeconds(30);
        $this->casualRequest('Newcomer1');
        $response = $this->casualRequest('Newcomer2');

        $this->assertEquals('spawn', $response->json('type'), json_encode($response->json()));
        $this->assertEquals('203.0.113.10', $response->json('spawn.Tunnel.Ip'));
        $this->assertEquals('0.0.0.0', $response->json('spawn.Other1.Ip'));
    }

    public function test_match_is_not_sent_when_its_tunnel_is_unknown(): void
    {
        config(['qm.casual_tunnels' => [['ip' => '203.0.113.10', 'port' => 50000, 'name' => 'Test Tunnel']]]);
        Http::fake(['203.0.113.10:50000/*' => Http::response('[1234,-5678]', 200)]);

        $this->casualRequest('Newcomer1');
        $this->waitSeconds(5);
        $this->casualRequest('Newcomer2');

        // The tunnel of the match is lost, e.g. because the cache was cleared
        Cache::flush();
        $response = $this->casualRequest('Newcomer1');

        $this->assertEquals('please wait', $response->json('type'), json_encode($response->json()));
    }

    public function test_players_that_stopped_polling_are_not_matched(): void
    {
        $this->casualRequest('Newcomer1');

        $this->waitSeconds(15);
        $response = $this->casualRequest('Newcomer2');

        $this->assertEquals('please wait', $response->json('type'));
        $this->assertEquals(0, QmMatch::count());
    }

    public function test_casual_matches_do_not_use_the_matchmaking_queue(): void
    {
        Queue::fake();

        $this->casualRequest('Newcomer1');
        $this->waitSeconds(5);
        $response = $this->casualRequest('Newcomer2');

        $this->assertEquals('spawn', $response->json('type'), json_encode($response->json()));
        Queue::assertNothingPushed();
    }

    public function test_players_are_not_matched_while_another_matchup_runs(): void
    {
        $this->casualRequest('Newcomer1');
        $this->waitSeconds(5);

        // Another request is matching players on this ladder
        $lock = Cache::lock('qm_casual_matchup:' . $this->ladder->current_history->id, 30);
        $lock->get();
        $response = $this->casualRequest('Newcomer2');
        $lock->release();

        $this->assertEquals('please wait', $response->json('type'));
        $this->assertEquals(0, QmMatch::count());

        $response = $this->casualRequest('Newcomer1');

        $this->assertEquals('spawn', $response->json('type'), json_encode($response->json()));
        $this->assertEquals(1, QmMatch::count());
    }

    public function test_failed_launch_detection_skips_casual_matches(): void
    {
        $this->casualRequest('Newcomer1');
        $this->waitSeconds(5);
        $this->casualRequest('Newcomer2');
        $casualMatch = QmMatch::where('ladder_id', $this->ladder->id)->first();

        $rankedMatch = $this->makeQmMatch($this->rankedLadder, $this->rankedLadder->mapPool->maps->first());
        $rankedPlayer = $this->makePlayerForLadder('RankedPlayer', $this->rankedLadder, $this->makeUser('RankedPlayer'));
        $this->makeQmMatchPlayer($rankedPlayer, $this->rankedLadder, $rankedMatch);

        // Neither match reports a result: casual matches never do, the ranked one failed to launch
        $this->waitSeconds(30 * 60);
        $this->artisan('qm:detect-failed-launches')->assertSuccessful();

        $this->assertEquals(0, QmCanceledMatch::where('qm_match_id', $casualMatch->id)->count());
        $this->assertEquals(1, QmCanceledMatch::where('qm_match_id', $rankedMatch->id)->count());
    }

    public function test_other_clients_cannot_cancel_a_search(): void
    {
        $this->casualRequest('Newcomer1');

        $this->casualRequest('Newcomer1', ['type' => 'quit', 'search_token' => md5('someone else')]);
        $this->assertEquals(1, QmQueueEntry::count());

        $this->casualRequest('Newcomer1', ['type' => 'quit']);
        $this->assertEquals(0, QmQueueEntry::count());
    }

    public function test_other_clients_cannot_receive_a_players_match(): void
    {
        $this->casualRequest('Newcomer1');
        $this->waitSeconds(5);
        $this->casualRequest('Newcomer2');

        $response = $this->casualRequest('Newcomer1', ['search_token' => md5('someone else')]);
        $this->assertEquals('fatal', $response->json('type'), json_encode($response->json()));

        $response = $this->casualRequest('Newcomer1');
        $this->assertEquals('spawn', $response->json('type'), json_encode($response->json()));
    }

    public function test_searches_need_a_search_token(): void
    {
        $response = $this->casualRequest('Newcomer1', ['search_token' => '']);

        $this->assertEquals('fatal', $response->json('type'));
        $this->assertEquals(0, Player::count());
    }

    public function test_casual_names_never_use_an_existing_account(): void
    {
        // Any email address can be registered on the website
        $user = $this->makeUser('Squatter');
        $user->email = 'newcomer1@casual.invalid';
        $user->save();

        $this->casualRequest('Newcomer1');

        $this->assertNotEquals($user->id, Player::where('username', 'Newcomer1')->first()->user_id);
    }

    public function test_a_name_has_one_casual_account_on_all_casual_ladders(): void
    {
        $this->artisan('qm:setup-casual-ladders')->assertSuccessful();

        $this->casualRequest('Newcomer1');
        $this->casualRequest('Newcomer1', [], 'yr-casual-2v2');

        $this->assertEquals(2, Player::where('username', 'Newcomer1')->count());
        $this->assertEquals(1, Player::where('username', 'Newcomer1')->distinct()->count('user_id'));
    }

    public function test_new_names_from_one_connection_are_limited(): void
    {
        for ($i = 1; $i <= 10; $i++)
        {
            $this->assertNotEquals('fatal', $this->casualRequest("Newcomer{$i}")->json('type'), "Newcomer{$i}");
        }

        $this->assertEquals('fatal', $this->casualRequest('Newcomer11')->json('type'));

        // Names that were used before keep working
        $this->assertNotEquals('fatal', $this->casualRequest('Newcomer1')->json('type'));
    }

    public function test_only_the_fields_that_casual_matchmaking_uses_are_stored(): void
    {
        $response = $this->casualRequest('Newcomer1', [
            'lan_ip' => 'not-an-ip',
            'ipv6_address' => '2001:db8::1',
            'ddraw' => 'custom',
            'colors' => [1, 2, 3],
            'client_version' => str_repeat('x', 100),
        ]);

        $this->assertEquals('please wait', $response->json('type'), json_encode($response->json()));
        $qmPlayer = QmMatchPlayer::first();
        $this->assertNull($qmPlayer->lan_address_id);
        $this->assertNull($qmPlayer->ipv6_address_id);
        $this->assertNull($qmPlayer->ddraw_id);
        $this->assertNull($qmPlayer->colors_pref);
        $this->assertNull($qmPlayer->client_version);
    }

    public function test_players_sharing_a_connection_are_not_rate_limited_too_soon(): void
    {
        // A searching client sends about 16 requests per minute, so this is about five searching players
        for ($i = 0; $i < 80; $i++)
        {
            $response = $this->getJson('/api/v1/qm/casual/queue-counts');
        }

        $response->assertStatus(200);
    }

    public function test_ladders_with_players_keep_their_matchmaking_type(): void
    {
        $admin = $this->makeUser('Admin');
        $admin->group = User::God;
        $admin->save();
        $saveLadder = fn($ladder, bool $isCasual) => $this->actingAs($admin)->post("/admin/setup/{$ladder->id}/ladder", [
            'name' => $ladder->name,
            'abbreviation' => $ladder->abbreviation,
            'game' => $ladder->game,
            'clans_allowed' => 0,
            'game_object_schema_id' => $ladder->game_object_schema_id,
            'private' => 0,
            'is_casual' => $isCasual ? 1 : 0,
        ]);

        $this->casualRequest('Newcomer1');
        $saveLadder($this->ladder, false);
        $this->assertTrue((bool)$this->ladder->fresh()->is_casual);

        // Ladders without players can still be changed
        $saveLadder($this->rankedLadder, true);
        $this->assertTrue((bool)$this->rankedLadder->fresh()->is_casual);
    }

    public function test_quit_removes_the_player_from_the_queue(): void
    {
        $this->casualRequest('Newcomer1');
        $this->assertEquals(1, QmQueueEntry::count());

        $response = $this->casualRequest('Newcomer1', ['type' => 'quit']);

        $this->assertEquals('quit', $response->json('type'));
        $this->assertEquals(0, QmQueueEntry::count());
    }

    public function test_player_that_quit_before_receiving_the_match_is_not_sent_to_it_later(): void
    {
        $this->casualRequest('Newcomer1');
        $this->waitSeconds(5);
        $this->casualRequest('Newcomer2');

        $this->casualRequest('Newcomer1', ['type' => 'quit']);
        $this->waitSeconds(5);
        $response = $this->casualRequest('Newcomer1');

        $this->assertEquals('please wait', $response->json('type'), json_encode($response->json()));
    }

    public function test_match_is_not_sent_after_the_search_was_abandoned(): void
    {
        $this->casualRequest('Newcomer1');
        $this->waitSeconds(5);
        $this->casualRequest('Newcomer2');

        // Newcomer1's client crashed before it received the match and searches again later
        $this->waitSeconds(3600);
        $response = $this->casualRequest('Newcomer1');

        $this->assertEquals('please wait', $response->json('type'), json_encode($response->json()));
    }

    public function test_new_search_uses_the_current_game_files(): void
    {
        $this->casualRequest('Newcomer1', ['client_version' => 'old-files']);

        // Newcomer1's client crashed, then the game was updated
        $this->waitSeconds(3600);
        $this->casualRequest('Newcomer1', ['client_version' => 'new-files']);
        $this->waitSeconds(5);
        $response = $this->casualRequest('Newcomer2', ['client_version' => 'new-files']);

        $this->assertEquals('spawn', $response->json('type'), json_encode($response->json()));
    }

    public function test_team_players_go_back_to_the_queue_when_a_player_quits_before_the_match_starts(): void
    {
        $this->artisan('qm:setup-casual-ladders')->assertSuccessful();
        $teamRequest = fn(string $playerName, array $data = []) => $this->casualRequest($playerName, $data, 'yr-casual-2v2');

        foreach (['Newcomer1', 'Newcomer2', 'Newcomer3', 'Newcomer4'] as $playerName)
        {
            $this->waitSeconds(1);
            $response = $teamRequest($playerName);
        }
        $this->assertEquals('spawn', $response->json('type'), json_encode($response->json()));

        $teamRequest('Newcomer1', ['type' => 'quit']);
        $response = $teamRequest('Newcomer2');
        $this->assertEquals('please wait', $response->json('type'), json_encode($response->json()));

        // Newcomer2 is back in the queue
        $this->waitSeconds(5);
        $response = $teamRequest('Newcomer2');
        $this->assertEquals('please wait', $response->json('type'), json_encode($response->json()));
        $this->assertEquals(1, QmQueueEntry::count());
    }

    public function test_queue_counts_only_include_casual_ladders(): void
    {
        $this->casualRequest('Newcomer1');

        $counts = $this->getJson('/api/v1/qm/casual/queue-counts')->json();

        $this->assertEquals([$this->ladder->abbreviation => 1], $counts);
    }

    public function test_casual_request_is_rejected_on_ranked_ladder(): void
    {
        $response = $this->postJson('/api/v1/qm/casual/' . $this->rankedLadder->abbreviation . '/Newcomer1', $this->matchRequest);

        $this->assertEquals('fatal', $response->json('type'));
        $this->assertEquals(0, Player::where('username', 'Newcomer1')->count());
    }

    public function test_ranked_request_is_rejected_on_casual_ladder(): void
    {
        $user = $this->makeUser('ranked_user');
        $player = $this->makePlayerForLadder('ranked_player', $this->ladder, $user);

        $response = $this
            ->jwtAuth($user)
            ->postJson('/api/v1/qm/' . $this->ladder->abbreviation . '/' . $player->username, $this->matchRequest + ['map_sides' => [1, 1, 1, 1]]);

        $this->assertEquals('fatal', $response->json('type'), json_encode($response->json()));
        $this->assertEquals(0, QmQueueEntry::count());
    }

    public function test_ranked_route_requires_authentication_even_with_casual_flag(): void
    {
        $response = $this->postJson('/api/v1/qm/' . $this->rankedLadder->abbreviation . '/Newcomer1', [
            'version' => '1.83',
            'type' => 'quit',
            'casual' => true,
        ]);

        $response->assertStatus(401);
    }

    public function test_registered_player_on_casual_ladder_cannot_be_taken_over(): void
    {
        $user = $this->makeUser('registered');
        $player = $this->makePlayerForLadder('registered', $this->ladder, $user);

        $response = $this->casualRequest($player->username, ['hwid' => 'other-hwid']);

        $this->assertEquals('fatal', $response->json('type'));
        $this->assertEquals(0, QmQueueEntry::count());
        $this->assertEquals(0, QmUserId::where('user_id', $user->id)->count());
    }

    public function test_name_of_registered_account_gets_separate_casual_account(): void
    {
        $registeredUser = $this->makeUser('RegUser');

        $response = $this->casualRequest('RegUser', ['hwid' => 'casual-hwid']);

        $this->assertEquals('please wait', $response->json('type'), json_encode($response->json()));

        $player = Player::where('username', 'RegUser')->first();
        $this->assertNotEquals($registeredUser->id, $player->user_id);
        $this->assertEquals(0, QmUserId::where('user_id', $registeredUser->id)->count());
    }

    public function test_accepts_any_valid_cncnet_nickname(): void
    {
        foreach (['A', 'Long_Nickname123', '[Clan]Pl\\yer', '{x}^`|'] as $name)
        {
            $response = $this->casualRequest($name);

            // Players queue up ("please wait") or are matched with the previous name ("spawn")
            $this->assertContains($response->json('type'), ['please wait', 'spawn'], $name . ': ' . json_encode($response->json()));
        }
    }

    public function test_rejects_invalid_player_names(): void
    {
        foreach (["Bad\nName", '1Player', '-Player', 'NameThatIsTooLong', 'Bad Name'] as $name)
        {
            $this->assertEquals('fatal', $this->casualRequest($name)->json('type'), $name);
        }

        $this->assertEquals(0, Player::count());
    }
}
