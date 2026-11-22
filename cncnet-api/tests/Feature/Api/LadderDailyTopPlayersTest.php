<?php

namespace Tests\Feature\Api;

use App\Http\Services\StatsService;
use App\Models\GameObjectSchema;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Player;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LadderDailyTopPlayersTest extends TestCase
{
    use RefreshDatabase;

    private Ladder $ladder;
    private LadderHistory $history;

    protected function setUp(): void
    {
        parent::setUp();

        // Freeze mid-day so the "today" window is stable
        $this->travelTo(Carbon::parse('2026-11-21 12:00:00', 'UTC'));

        $this->ladder = $this->makeLadder('tl');
        $this->history = $this->makeHistory($this->ladder);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function makeLadder(string $abbr, string $type = Ladder::ONE_VS_ONE): Ladder
    {
        $ladder = Ladder::factory()->create([
            'abbreviation' => $abbr,
            'game_object_schema_id' => GameObjectSchema::factory(),
        ]);
        $ladder->ladder_type = $type;
        $ladder->save();

        return $ladder;
    }

    private function makeHistory(Ladder $ladder): LadderHistory
    {
        return LadderHistory::factory()->create([
            'ladder_id' => $ladder->id,
            'starts' => now()->startOfMonth(),
            'ends' => now()->endOfMonth(),
        ]);
    }

    private function makePlayer(string $name, ?Ladder $ladder = null): Player
    {
        $user = User::create([
            'name' => $name,
            'email' => $name . '@test.com',
            'password' => Hash::make($name),
        ]);

        return Player::create([
            'user_id' => $user->id,
            'username' => $name,
            'ladder_id' => ($ladder ?? $this->ladder)->id,
        ]);
    }

    /**
     * Record one game for a player with the given points.
     * Options: draw, spectator, valid, best_report, history, created_at
     */
    private function addGame(Player $player, int $points, array $opts = []): void
    {
        $history = $opts['history'] ?? $this->history;
        $createdAt = $opts['created_at'] ?? now();

        $gameId = DB::table('games')->insertGetId([
            'ladder_history_id' => $history->id,
            'wol_game_id' => 0,
            'crat' => 0, 'cred' => '', 'shrt' => 0, 'supr' => 0, 'unit' => 0, 'plrs' => 2,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $gameReportId = DB::table('game_reports')->insertGetId([
            'game_id' => $gameId,
            'player_id' => $player->id,
            'best_report' => $opts['best_report'] ?? true,
            'manual_report' => false,
            'duration' => 600,
            'valid' => $opts['valid'] ?? true,
            'finished' => true,
            'fps' => 60,
            'oos' => false,
            'clan_id' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        DB::table('player_game_reports')->insert([
            'game_id' => $gameId,
            'game_report_id' => $gameReportId,
            'player_id' => $player->id,
            'local_id' => 0,
            'local_team_id' => 0,
            'points' => $points,
            'won' => $points > 0,
            'draw' => $opts['draw'] ?? false,
            'spectator' => $opts['spectator'] ?? false,
            'clan_id' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /** Give a player a set of games from a list of point values. */
    private function addGames(Player $player, array $pointsList): void
    {
        foreach ($pointsList as $points)
        {
            $this->addGame($player, $points);
        }
    }

    private function names(array $entries): array
    {
        return array_column($entries, 'player');
    }

    private function fetch(string $abbr = 'tl')
    {
        return $this->getJson("/api/v1/ladder/$abbr/today");
    }

    private function countPlayerGameReportQueries(): int
    {
        return collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'player_game_reports'))
            ->count();
    }

    // ---------------------------------------------------------------------
    // Characterization: getPlayerTimeRangeStats (NFR-004)
    // ---------------------------------------------------------------------

    public function test_player_time_range_stats_follow_existing_win_loss_rules(): void
    {
        $p = $this->makePlayer('char');
        $otherLadder = $this->makeLadder('ol');
        $otherHistory = $this->makeHistory($otherLadder);

        $this->addGame($p, 12);                                   // 2v2 dead-but-team-won: points>0 => win
        $this->addGame($p, -10);                                  // loss
        $this->addGame($p, 0);                                    // loss with 0 points, not draw
        $this->addGame($p, 0, ['draw' => true]);                  // draw: neither
        $this->addGame($p, 50, ['spectator' => true]);            // excluded
        $this->addGame($p, 50, ['valid' => false]);               // excluded
        $this->addGame($p, 50, ['best_report' => false]);         // excluded
        $this->addGame($p, 50, ['history' => $otherHistory]);     // excluded
        $this->addGame($p, 50, ['created_at' => now()->subDay()->endOfDay()]); // excluded

        $stats = (new StatsService())->getPlayerTimeRangeStats(
            $p, $this->history, now()->startOfDay(), now()->endOfDay()
        );

        $this->assertSame(['wins' => 1, 'losses' => 2, 'points' => 2], $stats);
    }

    public function test_player_time_range_stats_returns_zeros_with_no_games(): void
    {
        $p = $this->makePlayer('nobody');

        $stats = (new StatsService())->getPlayerTimeRangeStats(
            $p, $this->history, now()->startOfDay(), now()->endOfDay()
        );

        $this->assertSame(['wins' => 0, 'losses' => 0, 'points' => 0], $stats);
    }

    // ---------------------------------------------------------------------
    // Ranking (FR-002, FR-005, FR-007 to FR-010)
    // ---------------------------------------------------------------------

    public function test_entry_shape_and_point_breakdown(): void
    {
        $alice = $this->makePlayer('alice');
        $this->addGames($alice, [20, 18, 14, -12]);
        $this->addGame($alice, 0, ['draw' => true]);

        $response = $this->fetch()->assertOk();

        $response->assertJson([
            'ladder' => 'tl',
            'date' => '2026-11-21',
            'worst' => [],
        ]);
        $this->assertSame([[
            'player' => 'alice',
            'wins' => 3,
            'losses' => 1,
            'points_won' => 52,
            'points_lost' => 12,
            'points' => 40,
            'games' => 5,
        ]], $response->json('best'));
    }

    public function test_best_ordering_and_tie_breaks(): void
    {
        $this->addGames($this->makePlayer('A'), [20, 20, 20, 20, 20, 20]);        // +120 6W/0L
        $this->addGames($this->makePlayer('B'), [24, 24, 24, 24, 24]);            // +120 5W/0L
        $this->addGames($this->makePlayer('C'), [95]);                            // +95
        $this->addGames($this->makePlayer('D'), [25, 25, 25, 25, -20]);           // +80 4W/1L
        $this->addGames($this->makePlayer('E'), [20, 20, 20, 20]);                // +80 4W/0L
        $this->addGames($this->makePlayer('F'), [40]);                            // +40
        $this->addGames($this->makePlayer('G'), [10]);                            // +10

        $best = $this->fetch()->assertOk()->json('best');

        $this->assertSame(['A', 'B', 'C', 'E', 'D'], $this->names($best));
    }

    public function test_best_ranks_by_net_not_gross(): void
    {
        $this->addGames($this->makePlayer('P'), [50, 50, -45, -45]); // won 100, lost 90 => +10
        $this->addGames($this->makePlayer('Q'), [40, 40]);           // +80

        $best = $this->fetch()->assertOk()->json('best');

        $this->assertSame(['Q', 'P'], $this->names($best));
    }

    public function test_best_returns_shorter_list_when_fewer_than_five(): void
    {
        $this->addGames($this->makePlayer('one'), [10]);
        $this->addGames($this->makePlayer('two'), [20]);

        $best = $this->fetch()->assertOk()->json('best');

        $this->assertCount(2, $best);
    }

    public function test_worst_ordering_and_tie_breaks(): void
    {
        $this->addGames($this->makePlayer('X'), [-15, -15, -15, -15, -15, -15]); // -90 6L
        $this->addGames($this->makePlayer('Y'), [-30, -20, -20, -20]);           // -90 4L
        $this->addGames($this->makePlayer('Z'), [-60]);                          // -60

        $worst = $this->fetch()->assertOk()->json('worst');

        $this->assertSame(['X', 'Y', 'Z'], $this->names($worst));
    }

    public function test_net_zero_and_draw_only_players_appear_in_neither_list(): void
    {
        $this->addGames($this->makePlayer('ned'), [30, -30]);
        $this->addGame($this->makePlayer('dave'), 0, ['draw' => true]);

        $response = $this->fetch()->assertOk();

        $this->assertSame([], $response->json('best'));
        $this->assertSame([], $response->json('worst'));
    }

    public function test_no_player_in_both_lists(): void
    {
        foreach (range(1, 8) as $i)
        {
            $this->addGames($this->makePlayer("pos$i"), [$i * 10, -5]);
            $this->addGames($this->makePlayer("neg$i"), [-$i * 10, 5]);
        }

        $response = $this->fetch()->assertOk();

        $this->assertCount(5, $response->json('best'));
        $this->assertCount(5, $response->json('worst'));
        $this->assertEmpty(array_intersect(
            $this->names($response->json('best')),
            $this->names($response->json('worst'))
        ));
    }

    public function test_no_negative_points_loss_stored_as_zero_is_not_in_worst(): void
    {
        // Under no_negative_points a player at 0 has losses stored as points=0
        $this->addGames($this->makePlayer('eve'), [0, 0, 0]);

        $response = $this->fetch()->assertOk();
        $this->assertSame([], $response->json('worst'));

        $rows = (new StatsService())->getPlayerTimeRangeStats(
            Player::where('username', 'eve')->first(),
            $this->history,
            now()->startOfDay(),
            now()->endOfDay()
        );
        $this->assertSame(['wins' => 0, 'losses' => 3, 'points' => 0], $rows);
    }

    public function test_excluded_rows_do_not_contribute(): void
    {
        $p = $this->makePlayer('filtered');
        $otherLadder = $this->makeLadder('ol');
        $otherHistory = $this->makeHistory($otherLadder);

        $this->addGame($p, 50, ['valid' => false]);
        $this->addGame($p, 50, ['best_report' => false]);
        $this->addGame($p, 50, ['spectator' => true]);
        $this->addGame($p, 50, ['history' => $otherHistory]);
        $this->addGame($p, 50, ['created_at' => now()->subHours(25)]);

        $response = $this->fetch()->assertOk();

        $this->assertSame([], $response->json('best'));
        $this->assertSame([], $response->json('worst'));
    }

    public function test_no_games_returns_empty_arrays(): void
    {
        $this->fetch()
            ->assertOk()
            ->assertExactJson([
                'ladder' => 'tl',
                'date' => '2026-11-21',
                'hours' => 24,
                'from' => '2026-11-20T12:00:00+00:00',
                'to' => '2026-11-21T12:00:00+00:00',
                'best' => [],
                'worst' => [],
            ]);
    }

    // ---------------------------------------------------------------------
    // Rolling ?hours=N window
    // ---------------------------------------------------------------------

    public function test_default_window_is_rolling_24_hours(): void
    {
        $p = $this->makePlayer('roller');
        $this->addGame($p, 10, ['created_at' => now()->subHours(23)]); // yesterday, inside 24h
        $this->addGame($p, 99, ['created_at' => now()->subHours(25)]); // outside

        $response = $this->fetch()->assertOk();

        $this->assertSame(24, $response->json('hours'));
        $this->assertSame(10, $response->json('best.0.points'));
    }

    public function test_hours_48_extends_the_window(): void
    {
        $p = $this->makePlayer('roller');
        $this->addGame($p, 10, ['created_at' => now()->subHours(2)]);
        $this->addGame($p, 20, ['created_at' => now()->subHours(47)]);
        $this->addGame($p, 99, ['created_at' => now()->subHours(49)]);

        $response = $this->getJson('/api/v1/ladder/tl/today?hours=48')->assertOk();

        $this->assertSame(48, $response->json('hours'));
        $this->assertSame('2026-11-19T12:00:00+00:00', $response->json('from'));
        $this->assertSame(30, $response->json('best.0.points'));
        $this->assertSame(2, $response->json('best.0.games'));
    }

    public function test_different_hours_are_cached_separately(): void
    {
        $p = $this->makePlayer('roller');
        $this->addGame($p, 10, ['created_at' => now()->subHours(2)]);
        $this->addGame($p, 20, ['created_at' => now()->subHours(30)]);

        $this->assertSame(10, $this->getJson('/api/v1/ladder/tl/today?hours=24')->json('best.0.points'));
        $this->assertSame(30, $this->getJson('/api/v1/ladder/tl/today?hours=48')->json('best.0.points'));
    }

    public function test_window_does_not_reach_into_previous_month(): void
    {
        $this->travelTo(Carbon::parse('2026-12-01 10:00:00', 'UTC'));
        $november = $this->history;
        $december = $this->makeHistory($this->ladder);

        $p = $this->makePlayer('newmonth');
        $this->addGame($p, 10, ['history' => $december, 'created_at' => now()->subHours(1)]);
        $this->addGame($p, 50, ['history' => $november, 'created_at' => now()->subHours(20)]);
        // Even a current-history row timestamped before the month start is excluded
        $this->addGame($p, 70, ['history' => $december, 'created_at' => now()->subHours(12)]);

        $response = $this->getJson('/api/v1/ladder/tl/today?hours=48')->assertOk();

        $this->assertSame('2026-12-01T00:00:00+00:00', $response->json('from'));
        $this->assertSame(10, $response->json('best.0.points'));
        $this->assertSame(1, $response->json('best.0.games'));

        $single = $this->getJson('/api/v1/ladder/tl/player/newmonth/today?hours=48')->assertOk();
        $this->assertSame('2026-12-01T00:00:00+00:00', $single->json('from'));
        $this->assertSame(10, $single->json('points'));
    }

    public function test_invalid_hours_returns_422_on_both_endpoints(): void
    {
        $this->makePlayer('someone');

        // Note: an empty "?hours=" falls back to the default (24)
        $bad = ['0', '49', '-1', 'abc', '1.5'];
        $statuses = [];
        foreach ($bad as $value)
        {
            $ladder = $this->getJson("/api/v1/ladder/tl/today?hours=$value");
            $player = $this->getJson("/api/v1/ladder/tl/player/someone/today?hours=$value");
            $statuses[$value] = [$ladder->status(), $player->status(), $ladder->json('error')];
        }

        $expected = array_fill_keys($bad, [422, 422, 'hours must be an integer between 1 and 48']);
        $this->assertSame($expected, $statuses);
    }

    // ---------------------------------------------------------------------
    // Per-player /today endpoint
    // ---------------------------------------------------------------------

    public function test_player_endpoint_response_shape(): void
    {
        $p = $this->makePlayer('shape');
        $this->addGames($p, [20, -5]);
        $this->addGame($p, 15, ['created_at' => now()->subHours(30)]);

        $this->getJson('/api/v1/ladder/tl/player/shape/today')
            ->assertOk()
            ->assertExactJson([
                'player' => 'shape',
                'ladder' => 'tl',
                'date' => '2026-11-21',
                'hours' => 24,
                'from' => '2026-11-20T12:00:00+00:00',
                'to' => '2026-11-21T12:00:00+00:00',
                'wins' => 1,
                'losses' => 1,
                'points' => 15,
            ]);

        $this->getJson('/api/v1/ladder/tl/player/shape/today?hours=48')
            ->assertOk()
            ->assertJsonPath('wins', 2)
            ->assertJsonPath('points', 30);
    }

    public function test_player_endpoint_404_is_not_cached(): void
    {
        $this->getJson('/api/v1/ladder/tl/player/latecomer/today')
            ->assertNotFound()
            ->assertExactJson(['error' => 'Player not found']);

        $this->makePlayer('latecomer');

        $this->getJson('/api/v1/ladder/tl/player/latecomer/today')->assertOk();
    }

    // ---------------------------------------------------------------------
    // Errors (FR-011 to FR-013, NFR-003)
    // ---------------------------------------------------------------------

    public function test_unknown_ladder_returns_404(): void
    {
        $this->fetch('xyz')
            ->assertNotFound()
            ->assertExactJson(['error' => 'Ladder not found']);
    }

    public function test_ladder_without_current_history_returns_404(): void
    {
        $this->makeLadder('nohist');

        $this->fetch('nohist')
            ->assertNotFound()
            ->assertExactJson(['error' => 'No active ladder history']);
    }

    public function test_clan_ladder_returns_404(): void
    {
        $clanLadder = $this->makeLadder('blitz-clan', Ladder::CLAN_MATCH);
        $this->makeHistory($clanLadder);

        $this->fetch('blitz-clan')
            ->assertNotFound()
            ->assertExactJson(['error' => 'Not supported for clan ladders']);
    }

    public function test_404_is_not_cached(): void
    {
        $this->fetch('newladder')->assertNotFound();

        $ladder = $this->makeLadder('newladder');
        $this->makeHistory($ladder);

        $this->fetch('newladder')->assertOk();
    }

    // ---------------------------------------------------------------------
    // Performance & caching (NFR-001, NFR-002)
    // ---------------------------------------------------------------------

    public function test_cold_call_issues_one_aggregate_query_and_second_call_is_cached(): void
    {
        foreach (range(1, 10) as $i)
        {
            $this->addGames($this->makePlayer("p$i"), [$i * 3, -$i]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $first = $this->fetch()->assertOk()->json();
        $this->assertSame(1, $this->countPlayerGameReportQueries());

        DB::flushQueryLog();

        $second = $this->fetch()->assertOk()->json();
        $this->assertSame(0, $this->countPlayerGameReportQueries());
        $this->assertSame($first, $second);

        DB::disableQueryLog();
    }

    // ---------------------------------------------------------------------
    // Consistency with per-player endpoint (NFR-004)
    // ---------------------------------------------------------------------

    public function test_matches_per_player_today_endpoint(): void
    {
        $this->addGames($this->makePlayer('winner'), [20, 15, -8]);
        $this->addGame($this->makePlayer('winner2'), 7);
        $this->addGames($this->makePlayer('loser'), [-20, 0, 5]);
        $this->addGame(Player::where('username', 'loser')->first(), 0, ['draw' => true]);

        $list = $this->fetch()->assertOk()->json();
        $entries = collect(array_merge($list['best'], $list['worst']))->keyBy('player');

        foreach (['winner', 'winner2', 'loser'] as $name)
        {
            $single = $this->getJson("/api/v1/ladder/tl/player/$name/today")->assertOk()->json();

            $this->assertSame($single['wins'], $entries[$name]['wins'], "wins mismatch for $name");
            $this->assertSame($single['losses'], $entries[$name]['losses'], "losses mismatch for $name");
            $this->assertSame($single['points'], $entries[$name]['points'], "points mismatch for $name");
        }
    }
}
