<?php

namespace App\Http\Controllers;

use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\PlayerCache;
use App\Models\Player;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointSystemSimulationController extends Controller
{
    // Limit ladder to RA, RA2, YR, Blitz and 2v2-Blitz.
    private array $allowedLadderIds = [1, 3, 5, 8, 15];

    public function index(Request $request)
    {
        $start = microtime(true);

        $validated = $request->validate([
            'wol_k'                     => 'required|integer|between:0,128',
            'upset_k'                   => 'required|integer|between:0,64',
            'upset_k_loser_multiplier'  => 'required|numeric|between:0,1',
            'fixed_points'              => 'required|integer|between:0,32',
            'no_negative_points'        => 'required|boolean',
        ]);

        // Calculation might need a little more time...
        if (function_exists('set_time_limit'))
        {
            @set_time_limit(60);
        }
        
        if (function_exists('ini_set'))
        {
            @ini_set('max_execution_time', '60');
        }

        $ladders = Ladder::whereIn('id', $this->allowedLadderIds)
            ->orderByRaw("FIELD(id, " . implode(',', $this->allowedLadderIds) . ")")
            ->get(['id', 'name', 'abbreviation']);

        $defaultAbbrev  = optional($ladders->first())->abbreviation ?? 'yr';
        $selectedAbbrev = $request->query('abbreviation', $defaultAbbrev);
        $selectedMonth  = $request->query('month', Carbon::now()->format('Y-m'));
        $selectedPlayerId = (int) $request->query('player_id', 0);

        $ladder = Ladder::whereIn('id', $this->allowedLadderIds)
            ->where('abbreviation', $selectedAbbrev)
            ->first() ?? $ladders->first();

        // Create a list of the last 12 month.
        $monthOptions = [];
        for ($i = 0; $i < 12; $i++)
        {
            $m = Carbon::now()->startOfMonth()->subMonthsNoOverflow($i);
            $monthOptions[] = ['value' => $m->format('Y-m'), 'label' => $m->isoFormat('MMMM YYYY')];
        }

        try
        {
            $startOfMonth = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $startOfMonth = Carbon::now()->startOfMonth();
        }

        $endOfMonth = (clone $startOfMonth)->endOfMonth();

        $history = LadderHistory::where('ladder_id', $ladder->id)
            ->where('starts', '<=', $startOfMonth->toDateTimeString())
            ->where('ends', '>=', $endOfMonth->toDateTimeString())
            ->orderBy('starts', 'desc')
            ->first();

        $bailFps  = 0;
        $bailTime = 0;

        if ($history && $history->ladder && $history->ladder->qmLadderRules)
        {
            $rules = $history->ladder->qmLadderRules;
            /*$currentVariables = [
                'wol_k'                    => (int) $rules->wol_k,
                'upset_k'                  => (int) $rules->upset_k,
                'upset_k_loser_multiplier' => (float) $rules->upset_k_loser_multiplier,
                'fixed_points'             => (int) $rules->fixed_points,
                'no_negative_points'       => (bool) $rules->no_negative_points,
            ];
            */
            $bailFps  = (int) $rules->bail_fps;
            $bailTime = (int) $rules->bail_time;
        }

        $simParams = [
            'wol_k'                     => (int)   $validated['wol_k'],
            'upset_k'                   => (int)   $validated['upset_k'],
            'upset_k_loser_multiplier'  => (float) $validated['upset_k_loser_multiplier'],
            'fixed_points'              => (int)   $validated['fixed_points'],
            'no_negative_points'        => (bool)  $validated['no_negative_points'],
        ];

        // Get the cached points the ladder_history_id.
        $cacheStandings = collect();
        if ($history)
        {
            $cacheStandings = PlayerCache::where('ladder_history_id', $history->id)
                ->with(['player.user'])
                ->orderBy('points', 'DESC')->orderBy('player_name', 'ASC')
                ->get();
        }

        // Resolve alias for the cached standings.
        $this->attachDisplayNames($cacheStandings);

        // Determine cached ranks.
        $rankCached = [];
        foreach ($cacheStandings as $i => $row)
        {
            $rankCached[$row->player_id] = $i + 1;
        }

        // Count number of games for each player.
        $oldGamesByPlayer = $this->countPlayedGamesByPlayer($history);

        // Fill player dropdown with all active players in this month.
        $playerOptions = collect();
        $playerIds = collect();

        if ($history)
        {
            $playerIds = DB::table('player_game_reports as pgr')
                ->join('game_reports as gr', 'gr.id', '=', 'pgr.game_report_id')
                ->join('games as g', 'g.id', '=', 'gr.game_id')
                ->where('g.ladder_history_id', $history->id)
                ->where('gr.best_report', 1)
                ->where('gr.fps', '>=', $bailFps)
                ->where('gr.duration', '>=', $bailTime)
                ->where('pgr.spectator', 0)
                ->distinct()
                ->pluck('pgr.player_id')
                ->map(fn($v) => (int)$v)
                ->values();

            $players = Player::with('user')
                ->whereIn('id', $playerIds)
                ->get(['id','username','user_id']);
            $playerOptions = $players
                ->map(fn($p) => [
                    'id'    => (int) $p->id,
                    'label' => $p->user?->alias() ?: $p->username,
                ])
                ->sortBy('label', SORT_NATURAL|SORT_FLAG_CASE)
                ->values();
        }

        // Start actual simulation.
        $simulationRows = collect();
        $gameBreakdown = [];
        if ($history)
        {
            $simRes = $this->simulateMonthOptimized($history, $simParams, $bailFps, $bailTime, $selectedPlayerId ?: null, $playerIds);
            if (is_array($simRes))
            {
                $simulationRows = $simRes['totals'];
                $gameBreakdown  = $simRes['breakdown'];
            }
            else
            {
                $simulationRows = $simRes;
            }
        }

        // Ranks simulated.
        $sortedSim = $simulationRows->sortByDesc('points_sim')->values();
        $rankSimByPlayer = [];
        foreach ($sortedSim as $idx => $row)
        {
            $rankSimByPlayer[$row['player_id']] = $idx + 1;
        }

        $simIndex = $simulationRows->keyBy('player_id');

        $allPlayerIds = $cacheStandings->pluck('player_id')
            ->merge($simulationRows->pluck('player_id'))
            ->unique()
            ->values();

        $compare = collect();
        foreach ($allPlayerIds as $pid)
        {
            $old = $cacheStandings->firstWhere('player_id', $pid);
            $sim = $simIndex->get($pid);

            $rankOld = (int) ($rankCached[$pid] ?? 0);
            $rankSim = (int) ($rankSimByPlayer[$pid] ?? 0);

            // Δ Rank: sim - old (negative means improvement).
            $deltaRank = ($rankSim > 0 && $rankOld > 0) ? ($rankSim - $rankOld) : 0;

            $compare->push([
                'player_id'    => $pid,
                'display_name' => $old->display_name ?? ($sim['display_name'] ?? 'Unknown'),
                'points_old'   => (int) ($old->points ?? 0),
                'points_sim'   => (int) ($sim['points_sim'] ?? 0),
                'games_old'    => (int) ($oldGamesByPlayer[$pid] ?? 0),
                'games_sim'    => (int) ($sim['games_sim'] ?? 0),
                'rank_old'     => $rankOld,
                'rank_sim'     => $rankSim,
                'delta_rank'   => $deltaRank,
            ]);
        }

        // Sort by cached rank.
        $compare = $compare->sortBy([
            ['rank_old', 'asc'],
            ['rank_sim', 'asc'],
        ])->values();

        $duration = microtime(true) - $start;

        return view('admin.point-system-simulation', [
            'ladders'          => $ladders,
            'ladder'           => $ladder,
            'monthOptions'     => $monthOptions,
            'selectedAbbrev'   => $selectedAbbrev,
            'selectedMonth'    => $selectedMonth,
            'history'          => $history,
            'standings'        => $cacheStandings,
            'compare'          => $compare,
            'startOfMonth'     => $startOfMonth,
            'endOfMonth'       => $endOfMonth,
            'simParams'        => $simParams,
            // 'currentVariables' => $currentVariables,
            'playerOptions'    => $playerOptions,
            'selectedPlayerId' => $selectedPlayerId,
            'gameBreakdown'    => $gameBreakdown,
            'duration'         => $duration,
        ]);
    }

    private function simulateMonthOptimized(
        LadderHistory $history,
        array $rules,
        int $bailFps,
        int $bailTime,
        ?int $selectedPlayerId = null,
        ?\Illuminate\Support\Collection $playerIds
    ): Collection|array {
        $historyId = $history->id;
        $ladderId  = $history->ladder_id;

        if ($playerIds->isEmpty())
        {
            return $selectedPlayerId ? ['totals' => collect(), 'breakdown' => []] : collect();
        }

        $players = Player::with('user')
            ->whereIn('id', $playerIds)
            ->get(['id','username','user_id'])
            ->keyBy('id');

        // Create display name cache.
        $displayNameByPlayer = [];
        foreach ($players as $p)
        {
            $displayNameByPlayer[$p->id] = $p->user?->alias() ?? $p->username ?? 'Unknown';
        }

        // Create user rating cache.
        $ratingDevByPlayer = []; // [player_id => ['rating'=>int, 'deviation'=>float]]
        foreach ($players as $player) {
            $user = $player->user;
            if ($user)
            {
                $eff = $user->getEffectiveUserRatingForLadder($ladderId);
                $ratingDevByPlayer[$player->id] = [
                    'rating'    => (int)   ($eff['rating'] ?? 1500),
                    'deviation' => (float) ($eff['deviation'] ?? 350.0),
                ];
            }
            else
            {
                $ratingDevByPlayer[$player->id] = ['rating' => 1500, 'deviation' => 350.0];
            }
        }

        // Count number of games per player.
        $totalGamesByPlayer = DB::table('player_game_reports as pgr')
            ->join('game_reports as gr', 'gr.id', '=', 'pgr.game_report_id')
            ->join('games as g', 'g.id', '=', 'gr.game_id')
            ->where('g.ladder_history_id', $historyId)
            ->where('gr.best_report', 1)
            ->where('pgr.spectator', 0)
            ->groupBy('pgr.player_id')
            ->pluck(DB::raw('COUNT(*)'), 'pgr.player_id');

        // All best_reports (bail gefiltert).
        $reports = DB::table('game_reports as gr')
            ->join('games as g', 'g.id', '=', 'gr.game_id')
            ->where('g.ladder_history_id', $historyId)
            ->where('gr.best_report', 1)
            ->where('gr.fps', '>=', $bailFps)
            ->where('gr.duration', '>=', $bailTime)
            ->orderBy('g.created_at')->orderBy('g.id')
            ->select('gr.id as gr_id', 'gr.game_id', 'g.created_at')
            ->get();

        if ($reports->isEmpty())
        {
            return $selectedPlayerId ? ['totals' => collect(), 'breakdown' => []] : collect();
        }

        $grIds = $reports->pluck('gr_id');

        $pgrRows = DB::table('player_game_reports')
            ->whereIn('game_report_id', $grIds)
            ->where('spectator', 0)
            ->select('game_report_id', 'player_id', 'team', 'local_team_id', 'won', 'draw', 'defeated', 'disconnected', 'points')
            ->get()
            ->groupBy('game_report_id'); // gr_id => [rows]

        $pointsSoFar  = []; // [player_id => rolling points before the next game]
        $gamesSim     = []; // [player_id => number of simulated games]
        $displayNames = []; // [player_id => displayed game]

        // Use player usernames as a fallback.
        $playerUsernames = DB::table('players')
            ->whereIn('id', $playerIds)
            ->pluck('username', 'id'); // [player_id => username]

        // Breakdown for selected player.
        $breakdown = [];

        // No go through all games
        foreach ($reports as $report)
        {
            $rows = $pgrRows->get($report->gr_id) ?? collect();
            if ($rows->isEmpty())
                continue;

            // Use team key for 1v1 and 2v2 for simplification.
            $pgrPrepared = $rows->map(function ($row) {
                $row->team_key = $row->team ?: (string) $row->local_team_id;
                return $row;
            });

            // Determine the winner.
            $winningTeam = $this->winningTeamKey($pgrPrepared);
            $hasWinner = $winningTeam !== null;

            // Pre-calculation for all participants.
            $allyEnemyCache = []; // [player_id => [lots of stats...]
            foreach ($pgrPrepared as $pgr)
            {
                $pid = (int) $pgr->player_id;
                $displayNames[$pid] = $displayNames[$pid] ?? ($displayNameByPlayer[$pid] ?? 'Unknown');

                // Spiel zählt immer (auch bei Draw)
                if (!isset($gamesSim[$pid]))
                    $gamesSim[$pid] = 0;
                
                    $gamesSim[$pid]++;

                $myTeam = $pgr->team_key;
                $myTeamWon = ($winningTeam === $myTeam);

                $allyPoints = 0;
                $enemyPoints = 0;
                $allyEloSum = 0.0;
                $enemyEloSum = 0.0;
                $allyDeviationSum = 0.0;
                $enemyDeviationSum = 0.0;
                $allyCount = 0;
                $enemyCount = 0;
                $enemyGamesSum = 0;

                foreach ($pgrPrepared as $other)
                {
                    $opid = (int) $other->player_id;
                    $ptsBefore = $pointsSoFar[$opid] ?? 0;
                    $rating    = $ratingDevByPlayer[$opid]['rating']    ?? 1500;
                    $deviation = $ratingDevByPlayer[$opid]['deviation'] ?? 350.0;

                    if ($other->team_key === $myTeam)
                    {
                        $allyPoints += $ptsBefore;
                        $allyEloSum += $rating;
                        $allyDeviationSum += $deviation;
                        $allyCount++;
                    }
                    else
                    {
                        $enemyPoints += $ptsBefore;
                        $enemyEloSum += $rating;
                        $enemyDeviationSum += $deviation;
                        $enemyCount++;
                        $enemyGamesSum += (int) ($totalGamesByPlayer[$opid] ?? 0);
                    }
                }

                $allyEnemyCache[$pid] = [
                    'allyPts'           => $allyPoints,
                    'enemyPts'          => $enemyPoints,
                    'allyElo'           => $allyEloSum,
                    'enemyElo'          => $enemyEloSum,
                    'allyDeviationSum'  => $allyDeviationSum,
                    'enemyDeviationSum' => $enemyDeviationSum,
                    'enemyElo'          => $enemyEloSum,
                    'allyCount'         => $allyCount,
                    'enemyCount'        => $enemyCount,
                    'enemyGamesSum'     => $enemyGamesSum,
                    'myTeamWon'         => $myTeamWon,
                    'draw'              => $pgr->draw ? 1 : 0,
                    'team'              => $myTeam,
                    'won'               => (bool) $pgr->won,
                ];
            }

            // Now calculate and add the points for each player.
            $perPlayerNewPoints = []; // pid => int
            foreach ($pgrPrepared as $pgr)
            {
                $pid = (int) $pgr->player_id;

                $calc = $allyEnemyCache[$pid];
                $total = 0;

                if (!$calc['draw'] && $hasWinner)
                {
                    $wol = $this->computeWOL($calc['allyPts'], $calc['enemyPts'], $calc['myTeamWon'], (int) $rules['wol_k']);
                    if (!$calc['myTeamWon'] && $calc['enemyGamesSum'] < 10)
                    {
                        $wol = (int) floor($wol * ($calc['enemyGamesSum'] / 10));
                    }

                    $fixed = $calc['myTeamWon'] ? (int) $rules['fixed_points'] : -(int) $rules['fixed_points'];

                    $upset = 0;
                    if ($calc['allyCount'] > 0 && $calc['enemyCount'] > 0 && $calc['allyCount'] == $calc['enemyCount'])
                    {
                        $avgDevForWeight = max($calc['allyDeviationSum'] / $calc['allyCount'], $calc['enemyDeviationSum'] / $calc['enemyCount']);
                        $devWeight = $avgDevForWeight <= 100 ? 1.0 : ($avgDevForWeight >= 200 ? 0.0 : 1.0 - (($avgDevForWeight - 100.0) / 100.0));

                        if ($calc['myTeamWon'])
                        {
                            $upset = $devWeight * $this->computeUpset($calc['allyElo'], $calc['enemyElo'], (int) $rules['upset_k']);
                        }
                        else
                        {
                            $winUpset = $this->computeUpset($calc['enemyElo'], $calc['allyElo'], (int) $rules['upset_k']);
                            $upset = - (int) (floor($winUpset * (float) $rules['upset_k_loser_multiplier']) * $devWeight);
                        }
                    }

                    $total = $fixed + $wol + $upset;

                    // no_negative_points (abhängig vom Start-Cache)
                    if ($rules['no_negative_points'])
                    {
                        $prior = $pointsSoFar[$pid] ?? null;
                        if ($total < 0 && ($prior === null || $prior < 0)) {
                            $total = 0;
                        }
                    }
                }

                $perPlayerNewPoints[$pid] = (int) $total;

                // Update points per user.
                if (!isset($pointsSoFar[$pid]))
                    $pointsSoFar[$pid] = 0;
                $pointsSoFar[$pid] += (int) $total;
            }

            // Create breakdown only if selected player participated.
            if ($selectedPlayerId && $pgrPrepared->contains(fn($x) => (int) $x->player_id === (int) $selectedPlayerId))
            {
                $participants = [];
                foreach ($pgrPrepared as $pgr)
                {
                    $pid = (int) $pgr->player_id;
                    $participants[] = [
                        'player_id'  => $pid,
                        'name'       => $displayNames[$pid] ?? ($playerUsernames[$pid] ?? 'Unknown'),
                        'old_points' => (int) $pgr->points,
                        'new_points' => (int) $perPlayerNewPoints[$pid],
                        'team'       => $allyEnemyCache[$pid]['team'],
                        'won'        => $allyEnemyCache[$pid]['won'],
                        'draw'       => (bool) ($allyEnemyCache[$pid]['draw'] ?? 0),
                    ];
                }

                // Move selected player to front.
                usort($participants, function ($a, $b) use ($selectedPlayerId) {
                    if (!$selectedPlayerId)
                        return 0;
                    if ($a['player_id'] === $selectedPlayerId && $b['player_id'] !== $selectedPlayerId)
                        return -1;
                    if ($b['player_id'] === $selectedPlayerId && $a['player_id'] !== $selectedPlayerId)
                        return 1;
                    return 0;
                });
                $breakdown[] = [
                    'game_id'      => (int) $report->game_id,
                    'created_at'   => $report->created_at,
                    'winningTeam'  => $winningTeam,
                    'participants' => $participants,
                ];
            }
        }

        $rows = collect();
        foreach ($pointsSoFar as $pid => $pts)
        {
            $rows->push([
                'player_id'    => (int) $pid,
                'display_name' => $displayNames[$pid] ?? ($playerUsernames[$pid] ?? 'Unknown'),
                'points_sim'   => (int) $pts,
                'games_sim'    => (int) ($gamesSim[$pid] ?? 0),
            ]);
        }

        return $selectedPlayerId ? ['totals' => $rows, 'breakdown' => $breakdown] : $rows;
    }

    private function winningTeamKey(Collection $pgrPrepared): ?string
    {
        foreach ($pgrPrepared as $r)
        {
            if ($r->won) return $r->team_key;
        }
        foreach ($pgrPrepared as $r)
        {
            if (!$r->defeated && !$r->disconnected) return $r->team_key;
        }
        return null;
    }

    private function computeWOL(int $allyPts, int $enemyPts, bool $myTeamWon, int $wolK): int
    {
        $diff = $enemyPts - $allyPts;
        $we = 1.0 / (pow(10.0, abs($diff) / 600.0) + 1.0);
        if (($diff > 0 && $myTeamWon) || ($diff < 0 && !$myTeamWon)) $we = 1.0 - $we;
        $wol = (int) floor($wolK * $we);
        return $myTeamWon ? $wol : -$wol;
    }

    private function computeUpset(float $winEloSum, float $loseEloSum, int $upsetK): int
    {
        $pWin = 1.0 / (1.0 + pow(10.0, ($loseEloSum - $winEloSum) / 400.0));
        return (int) floor((1.0 - $pWin) * $upsetK);
    }

    private function countPlayedGamesByPlayer(?LadderHistory $history): array
    {
        if (!$history)
        {
            return [];
        }

        $rows = DB::table('player_game_reports as pgr')
            ->join('game_reports as gr', 'gr.id', '=', 'pgr.game_report_id')
            ->join('games as g', 'g.id', '=', 'gr.game_id')
            ->where('g.ladder_history_id', $history->id)
            ->where('gr.best_report', 1)
            ->where('pgr.spectator', 0)
            ->groupBy('pgr.player_id')
            ->selectRaw('pgr.player_id, COUNT(*) as c')
            ->get();

        $out = [];
        foreach ($rows as $r)
        {
            $out[$r->player_id] = (int) $r->c;
        }
        return $out;
    }

    private function attachDisplayNames(\Illuminate\Support\Collection $standings): void
    {
        if ($standings->isEmpty())
            return;

        $standings->transform(function ($row)
        {
            $player = $row->player ?? null;
            $user = $player?->user;
            $playerName = $row->player_name ?? $player?->username ?? 'Unknown';
            $row->display_name = $user?->alias() ?: $playerName;
            $row->ladder_username = $playerName;
            return $row;
        });
    }
}