<?php

namespace Database\Seeders;

use App\Models\Ladder;
use App\Models\LadderEvent;
use App\Models\LadderHistory;
use App\Services\LadderEvents\LadderEventService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * LOCAL ONLY: fills the RA2 2v2 holiday event with fake demo users and games so the
 * dashboard can be previewed before the event starts.
 *
 *   docker exec dev_cncnet_ladder_app php artisan db:seed --class=LadderEventDemoSeeder
 *   open http://localhost:3000/events/ra2-2v2-holiday-2026?as_of=2027-01-20
 *
 * Remove it again with:
 *   docker exec dev_cncnet_ladder_app php artisan db:seed --class=LadderEventDemoCleanupSeeder
 *
 * Demo users have emails ending in @event-demo.test; demo games have wol_game_id = DEMO_MARKER.
 * Re-running the seeder replaces the previous demo data. Real users and group members are not touched.
 */
class LadderEventDemoSeeder extends Seeder
{
    const SLUG = 'ra2-2v2-holiday-2026';
    const EMAIL_DOMAIN = '@event-demo.test';
    const DEMO_MARKER = 424242424;
    const SIMULATED_NOW = '2027-01-20 23:59:59';

    /**
     * [alias, skill 1-10, activity 1-10, favourite country (sides.local_id), role]
     * Roles put the user in a named group, or make them a streamer.
     */
    const USERS = [
        ['DemoAce', 10, 7, 7, 's_class'],
        ['DemoBlitz', 9, 8, 1, 's_class'],
        ['DemoComet', 9, 5, 2, 's_class'],
        ['DemoDingo', 6, 6, 4, 'partner_mikoz'],
        ['DemoEcho', 7, 7, 0, 'partner_zedd_beta'],
        ['DemoFalcon', 6, 5, 3, 'partner_zedd_beta'],
        ['DemoGhost', 7, 6, 8, 'partner_niek_dayi'],
        ['DemoHawk', 5, 6, 5, 'partner_niek_dayi'],
        ['DemoIris', 6, 7, 7, 'selector_gino'],
        ['DemoJolt', 8, 6, 2, 'selector_edd'],
        ['DemoKoda', 5, 5, 4, 'selector_steve'],
        ['DemoLynx', 7, 8, 1, 'selector_sander'],
        ['DemoMako', 4, 4, 0, 'selector_andy'],
        ['DemoNova', 6, 9, 3, 'stream_player'],
        ['DemoOrca', 5, 7, 5, 'stream_player'],
        ['DemoPike', 3, 3, 8, 'stream_observer'],
        ['DemoQuill', 4, 2, 6, 'stream_observer'],
        ['DemoRaven', 8, 9, 7, null],
        ['DemoSable', 5, 8, 0, null],
        ['DemoTitan', 4, 10, 1, null],
        ['DemoUmbra', 3, 6, 2, null],
        ['DemoVolt', 6, 4, 3, null],
        ['DemoWren', 2, 7, 4, null],
        ['DemoYeti', 5, 5, 5, null],
    ];

    const MAPS = ['Tour of Egypt', 'Heck Freezes Over', 'Little Big Lake', 'Sedona Pass', 'Golden State Fwy', 'Dune Patrol'];

    public function run(): void
    {
        if (!app()->environment('local', 'testing'))
        {
            $this->command?->error('LadderEventDemoSeeder only runs in the local environment.');
            return;
        }

        $event = LadderEvent::where('slug', '=', self::SLUG)->first();
        if ($event === null)
        {
            $this->command?->error('Event not found. Run LadderEventRa2Holiday2026Seeder first.');
            return;
        }

        (new LadderEventDemoCleanupSeeder())->clean();

        mt_srand(2026);   // same demo every time
        $ladder = Ladder::find($event->ladder_id);
        $histories = $event->histories()->keyBy('short');
        $now = Carbon::now();
        $password = Hash::make(str()->random(32));   // demo accounts cannot be logged into

        // --- Users, players, group membership -------------------------------------------------
        $users = [];
        foreach (self::USERS as [$alias, $skill, $activity, $country, $role])
        {
            $userId = DB::table('users')->insertGetId([
                'name' => strtolower($alias) . '_account',
                'email' => strtolower($alias) . self::EMAIL_DOMAIN,
                'password' => $password,
                'alias' => $alias,
                'twitch_profile' => str_starts_with((string) $role, 'stream') ? strtolower($alias) : null,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $playerId = DB::table('players')->insertGetId([
                'user_id' => $userId, 'username' => $alias, 'ladder_id' => $ladder->id, 'card_id' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($role !== null && !str_starts_with($role, 'stream'))
            {
                DB::table('ladder_event_group_members')->insert([
                    'ladder_event_id' => $event->id, 'group_key' => $role, 'user_id' => $userId,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $users[] = compact('alias', 'skill', 'activity', 'country', 'role', 'userId', 'playerId');
        }

        // An alt account for DemoRaven, to show alts being combined (FR-002)
        $raven = collect($users)->firstWhere('alias', 'DemoRaven');
        $altUserId = DB::table('users')->insertGetId([
            'name' => 'demoraven_smurf', 'email' => 'demoraven.alt' . self::EMAIL_DOMAIN, 'password' => $password,
            'alias' => 'RavenSmurf', 'primary_user_id' => $raven['userId'], 'created_at' => $now, 'updated_at' => $now,
        ]);
        $altPlayerId = DB::table('players')->insertGetId([
            'user_id' => $altUserId, 'username' => 'RavenSmurf', 'ladder_id' => $ladder->id, 'card_id' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $players = array_values(array_filter($users, fn ($u) => $u['role'] !== 'stream_observer'));
        $observers = array_values(array_filter($users, fn ($u) => $u['role'] === 'stream_observer'));

        // --- Game times: steady play plus busy holidays --------------------------------------
        $start = Carbon::parse('2026-12-01 00:00:00', 'UTC')->getTimestamp();
        $end = Carbon::parse(self::SIMULATED_NOW, 'UTC')->getTimestamp();
        $times = [];
        for ($i = 0; $i < 720; $i++)
        {
            $times[] = mt_rand($start, $end);
        }
        foreach (['2026-12-25', '2027-01-01'] as $day)
        {
            $d = Carbon::parse($day, 'UTC')->getTimestamp();
            for ($i = 0; $i < 45; $i++)
            {
                $times[] = $d + mt_rand(0, 86399);
            }
        }
        sort($times);

        // --- Games ----------------------------------------------------------------------------
        $ids = [
            'games' => (int) DB::table('games')->max('id') + 1,
            'game_reports' => (int) DB::table('game_reports')->max('id') + 1,
            'player_game_reports' => (int) DB::table('player_game_reports')->max('id') + 1,
            'stats2' => (int) DB::table('stats2')->max('id') + 1,
            'qm_matches' => (int) DB::table('qm_matches')->max('id') + 1,
        ];
        $rows = ['games' => [], 'game_reports' => [], 'player_game_reports' => [], 'stats2' => [], 'qm_matches' => [], 'qm_match_players' => []];
        $cache = [];   // [history_id][player_id] => [points, wins, games]

        foreach ($times as $ts)
        {
            $at = Carbon::createFromTimestamp($ts, 'UTC');
            $history = $histories[$at->month === 1 ? '1-2027' : '12-2026'];
            $four = $this->pickPlayers($players, 4);
            [$teamA, $teamB] = [array_slice($four, 0, 2), array_slice($four, 2, 2)];

            $strengthA = $teamA[0]['skill'] + $teamA[1]['skill'];
            $strengthB = $teamB[0]['skill'] + $teamB[1]['skill'];
            $draw = mt_rand(1, 100) <= 3;
            $aWins = mt_rand(1, $strengthA + $strengthB) <= $strengthA;

            $gameId = $ids['games']++;
            $reportId = $ids['game_reports']++;
            $qmMatchId = $ids['qm_matches']++;
            $duration = mt_rand(480, 2700);

            $rows['qm_matches'][] = [
                'id' => $qmMatchId, 'ladder_id' => $ladder->id, 'qm_map_id' => 0, 'seed' => mt_rand(1, 99999), 'tier' => 1,
                'game_id' => $gameId, 'created_at' => $at, 'updated_at' => $at,
            ];
            $rows['games'][] = [
                'id' => $gameId, 'ladder_history_id' => $history->id, 'wol_game_id' => self::DEMO_MARKER,
                'crat' => 0, 'cred' => '10000', 'shrt' => 0, 'supr' => 0, 'unit' => 0, 'plrs' => 4,
                'scen' => self::MAPS[array_rand(self::MAPS)], 'game_report_id' => $reportId, 'qm_match_id' => $qmMatchId,
                'created_at' => $at, 'updated_at' => $at,
            ];
            $rows['game_reports'][] = [
                'id' => $reportId, 'game_id' => $gameId, 'player_id' => $teamA[0]['playerId'], 'best_report' => true,
                'manual_report' => false, 'duration' => $duration, 'valid' => true, 'finished' => true, 'fps' => 60,
                'oos' => false, 'clan_id' => 0, 'created_at' => $at, 'updated_at' => $at,
            ];

            foreach ([[$teamA, 'A', $aWins], [$teamB, 'B', !$aWins]] as [$team, $label, $won])
            {
                foreach ($team as $u)
                {
                    // DemoRaven plays a quarter of their games on the alt account
                    $playerId = ($u['alias'] === 'DemoRaven' && mt_rand(1, 4) === 1) ? $altPlayerId : $u['playerId'];
                    $points = $draw ? 0 : ($won ? mt_rand(12, 28) : -mt_rand(4, 14));
                    $pgrId = $ids['player_game_reports']++;
                    $hasStats = mt_rand(1, 100) <= 96;
                    $statsId = $hasStats ? $ids['stats2']++ : null;
                    $country = mt_rand(1, 100) <= 60 ? $u['country'] : mt_rand(0, 8);

                    $rows['player_game_reports'][] = [
                        'id' => $pgrId, 'game_id' => $gameId, 'game_report_id' => $reportId, 'player_id' => $playerId,
                        'local_id' => 0, 'local_team_id' => 0, 'team' => $label, 'points' => $points,
                        'stats_id' => $statsId, 'won' => !$draw && $won, 'defeated' => !$draw && !$won, 'draw' => $draw,
                        'spectator' => false, 'clan_id' => 0, 'created_at' => $at, 'updated_at' => $at,
                    ];
                    if ($hasStats)
                    {
                        $rows['stats2'][] = ['id' => $statsId, 'cty' => $country, 'player_game_report_id' => $pgrId];
                    }
                    $rows['qm_match_players'][] = $this->qmPlayer($ladder->id, $qmMatchId, $playerId, false, $u['role'] === 'stream_player' && mt_rand(1, 100) <= 80, $at);

                    $c = $cache[$history->id][$playerId] ?? [0, 0, 0];
                    $cache[$history->id][$playerId] = [$c[0] + max(0, $points), $c[1] + (!$draw && $won ? 1 : 0), $c[2] + 1];
                }
            }

            // A streaming observer watches about a third of the games
            if (mt_rand(1, 3) === 1)
            {
                $obs = $observers[array_rand($observers)];
                $rows['qm_match_players'][] = $this->qmPlayer($ladder->id, $qmMatchId, $obs['playerId'], true, true, $at);
            }
        }

        foreach ($rows as $table => $tableRows)
        {
            foreach (array_chunk($tableRows, 500) as $chunk)
            {
                DB::table($table)->insert($chunk);
            }
        }

        // --- Ladder points (player_caches) -----------------------------------------------------
        $cacheRows = [];
        $usernames = DB::table('players')->whereIn('id', array_merge(array_column($users, 'playerId'), [$altPlayerId]))->pluck('username', 'id');
        foreach ($cache as $historyId => $byPlayer)
        {
            foreach ($byPlayer as $playerId => [$points, $wins, $games])
            {
                $cacheRows[] = [
                    'ladder_history_id' => $historyId, 'player_id' => $playerId, 'player_name' => $usernames[$playerId],
                    'points' => $points, 'wins' => $wins, 'games' => $games, 'percentile' => 0, 'fps' => 60, 'tier' => 1,
                ];
            }
        }
        DB::table('player_caches')->insert($cacheRows);

        // --- Organiser input: manual winners and an override -----------------------------------
        $this->demoResults($event, $users);

        $payload = app(LadderEventService::class)->compute($event->fresh(), Carbon::parse(self::SIMULATED_NOW, 'UTC'));

        $this->command?->info(sprintf(
            'Demo seeded: %d users, %d games, %d standings rows, %d award cards.',
            count($users) + 1, count($rows['games']), count($payload['standings']), count($payload['awards'])
        ));
        $this->command?->info('Preview: http://localhost:3000/events/' . self::SLUG . '?as_of=2027-01-20');
    }

    /** Weighted by activity, distinct users. */
    private function pickPlayers(array $pool, int $n): array
    {
        $picked = [];
        while (count($picked) < $n)
        {
            $total = array_sum(array_map(fn ($u) => isset($picked[$u['userId']]) ? 0 : $u['activity'], $pool));
            $roll = mt_rand(1, $total);
            foreach ($pool as $u)
            {
                if (isset($picked[$u['userId']]))
                {
                    continue;
                }
                $roll -= $u['activity'];
                if ($roll <= 0)
                {
                    $picked[$u['userId']] = $u;
                    break;
                }
            }
        }
        $picked = array_values($picked);
        shuffle($picked);
        return $picked;
    }

    private function qmPlayer(int $ladderId, int $qmMatchId, int $playerId, bool $observer, bool $live, Carbon $at): array
    {
        return [
            'waiting' => false, 'player_id' => $playerId, 'ladder_id' => $ladderId, 'map_bitfield' => 0,
            'chosen_side' => 0, 'actual_side' => 0, 'tier' => 1, 'qm_match_id' => $qmMatchId,
            'is_observer' => $observer, 'twitch_live_at_start' => $live, 'created_at' => $at, 'updated_at' => $at,
        ];
    }

    private function demoResults(LadderEvent $event, array $users): void
    {
        $by = fn ($alias) => collect($users)->firstWhere('alias', $alias);
        $setBy = $by('DemoJolt')['userId'];
        $now = Carbon::now();

        // Giant Killer: an underdog win against an S-class pair
        $sClassPlayers = array_column(array_filter($users, fn ($u) => $u['role'] === 's_class'), 'playerId');
        $upset = DB::table('player_game_reports as winner')
            ->join('player_game_reports as loser', function ($j) {
                $j->on('loser.game_id', '=', 'winner.game_id')->on('loser.team', '!=', 'winner.team');
            })
            ->join('games', 'games.id', '=', 'winner.game_id')
            ->where('games.wol_game_id', '=', self::DEMO_MARKER)
            ->where('winner.player_id', '=', $by('DemoWren')['playerId'])
            ->where('winner.points', '>', 0)
            ->whereIn('loser.player_id', $sClassPlayers)
            ->orderBy('games.id')
            ->value('games.id');

        $results = [];
        if ($upset)
        {
            $results[] = ['award_key' => 'giant_killer', 'kind' => 'manual', 'user_id' => $by('DemoWren')['userId'],
                'game_id' => $upset, 'note' => 'Demo: the lowest-rated player beat an S-Class pair'];
        }

        // A-Class Gatekeeper Edd: one of the candidate wins partnered with DemoJolt (selector_edd)
        $candidate = DB::table('player_game_reports as me')
            ->join('player_game_reports as partner', function ($j) {
                $j->on('partner.game_id', '=', 'me.game_id')->on('partner.team', '=', 'me.team')->on('partner.id', '!=', 'me.id');
            })
            ->join('players', 'players.id', '=', 'me.player_id')
            ->where('partner.player_id', '=', $by('DemoJolt')['playerId'])
            ->where('me.points', '>', 0)
            ->orderByDesc('me.game_id')
            ->first(['me.game_id', 'players.user_id']);
        if ($candidate)
        {
            $results[] = ['award_key' => 'gatekeeper_edd', 'kind' => 'manual', 'user_id' => $candidate->user_id,
                'game_id' => $candidate->game_id, 'note' => 'Demo: picked by the selector'];
        }

        // An override, to show the "set by organisers" badge
        $results[] = ['award_key' => 'faction_russia', 'kind' => 'override', 'user_id' => $by('DemoHawk')['userId'],
            'game_id' => null, 'note' => 'Demo override: previous leader disqualified'];

        foreach ($results as $r)
        {
            DB::table('ladder_event_award_results')->insert($r + [
                'ladder_event_id' => $event->id, 'set_by' => $setBy, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
