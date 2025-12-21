<?php

namespace App\Http\Services;

use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\MapPool;
use App\Models\QmMatchPlayer;
use App\Models\QmMap;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FactionPolicyService
{
    protected function getForcedStats(Ladder $ladder, LadderHistory $history, QmMatchPlayer $player, int $forcedId): array
    {
        Log::debug('getForcedStats: start', [
            'ladder_id'  => $ladder->id,
            'history_id' => $history->id,
            'player_id'  => $player->player_id,
            'forcedId'   => $forcedId,
        ]);

        $query = DB::table('player_game_reports')
            ->join('game_reports', 'game_reports.id', '=', 'player_game_reports.game_report_id')
            ->join('games', 'games.id', '=', 'game_reports.game_id')
            ->join('stats2', 'stats2.id', '=', 'player_game_reports.stats_id')
            ->where('player_game_reports.player_id', $player->player_id)
            ->where('games.ladder_history_id', $history->id)
            ->where('game_reports.valid', 1)
            ->where('game_reports.best_report', 1);

        $sql = $query->toSql();
        Log::debug('getForcedStats: SQL', ['sql' => $sql, 'bindings' => $query->getBindings()]);

        $row = $query
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN stats2.cty = ? THEN 1 ELSE 0 END) AS forced_cnt', [$forcedId])
            ->first();

        Log::debug('getForcedStats: result row', (array)$row);

        return [(int) ($row->total ?? 0), (int) ($row->forced_cnt ?? 0)];
    }

    protected function createCandidates(array $currentSides, MapPool $pool): array
    {
        [$s1, $s2] = $currentSides;
        $forcedId  = (int)$pool->forced_faction_id;

        Log::debug('createCandidates: start', [
            'currentSides' => $currentSides,
            'forcedId'     => $forcedId,
        ]);

        // Collect candidates.
        $candidates = [ [$s1, $s2] ];
        $candidates[] = [ $forcedId, $s2 ];
        $candidates[] = [ $s1,       $forcedId ];
        $candidates[] = [ $forcedId, $forcedId ];

        Log::debug('createCandidates: raw candidates', $candidates);

        // Remove forbidden pairs.
        $valid = [];
        foreach ($candidates as [$faction1, $faction2])
        {
            $isValid = $pool->isValidPair($faction1, $faction2);
            Log::debug('createCandidates: check isValidPair', [
                'pair'    => [$faction1, $faction2],
                'isValid' => $isValid,
            ]);
            if (!$isValid)
            {
                Log::debug('createCandidates: rejected pair', [$faction1, $faction2]);
                continue;
            }
            $valid[] = [$faction1, $faction2];
        }

        Log::debug('createCandidates: valid candidates', $valid);
        return $valid;
    }

    public function getDelta(int $total, int $forced, float $ratio): float
    {
        if ($total === 0)
        {
            return $ratio - 1.0;
        }
        $current = $forced / $total;
        return $ratio - $current;
    }

    public function applyPolicy1v1(MapPool $pool, Ladder $ladder, LadderHistory $history, QmMap $qmMap, QmMatchPlayer $p1, QmMatchPlayer $p2): void
    {
        $forcedId = $pool->forced_faction_id !== null ? (int)$pool->forced_faction_id : null;
        $ratio = $pool->forced_faction_ratio !== null ? (float)$pool->forced_faction_ratio : null;

        Log::debug('applyPolicy1v1: start', [
            'forcedId' => $forcedId,
            'ratio'    => $ratio,
            'map'      => $qmMap->description,
            'p1_side'  => $p1->actual_side,
            'p2_side'  => $p2->actual_side,
            'map_sides'=> $qmMap->sides_array(),
        ]);

        if ($forcedId === null || $ratio === null)
        {
            // No existing policy.
            Log::debug('applyPolicy1v1: no policy set, abort');
            return;
        }

        $currentSides = [(int)$p1->actual_side, (int)$p2->actual_side];
        Log::debug('applyPolicy1v1: currentSides', $currentSides);

        if ($currentSides[0] < 0 || $currentSides[1] < 0)
        {
            Log::warning('applyPolicy1v1: random side encountered, skipping. p1='.$currentSides[0].' p2='.$currentSides[1]);
            return;
        }

        $candidates = $this->createCandidates($currentSides, $pool);

        // Now check which candidates are actually allowed for the map.
        $allowedSides = array_values(array_map('intval', array_filter($qmMap->sides_array(), fn($s) => (int)$s >= 0)));
        Log::debug('applyPolicy1v1: allowedSides (after intval)', $allowedSides);

        $filteredCandidates = [];
        foreach ($candidates as [$faction1, $faction2])
        {
            $in1 = in_array($faction1, $allowedSides, true);
            $in2 = in_array($faction2, $allowedSides, true);
            Log::debug('applyPolicy1v1: candidate check', [
                'candidate' => [$faction1, $faction2],
                'inAllowed1' => $in1,
                'inAllowed2' => $in2,
            ]);

            if (!$in1 || !$in2)
            {
                // Candidates contain factions, which are not allowed on this map.
                Log::debug('applyPolicy1v1: rejected candidate (not in allowedSides)', [
                    'f1' => $faction1,
                    'f2' => $faction2
                ]);
                continue;
            }
            $filteredCandidates[] = [$faction1, $faction2];
        }
        Log::debug('applyPolicy1v1: filteredCandidates', $filteredCandidates);

        if (empty($filteredCandidates))
        {
            Log::info('applyPolicy1v1: cannot apply faction policy on map ' . $qmMap->description);
            return;
        }

        if (count($filteredCandidates) === 1)
        {
            [$faction1, $faction2] = $filteredCandidates[0];
            Log::debug('applyPolicy1v1: only one candidate left', [
                'candidate' => [$faction1, $faction2],
                'current'   => $currentSides,
            ]);
            if ($faction1 !== $currentSides[0] || $faction2 !== $currentSides[1])
            {
                $p1->actual_side = $faction1;
                $p2->actual_side = $faction2;
                $p1->save();
                $p2->save();
                Log::info('applyPolicy1v1: forcing p1='. $p1->actual_side . ' and p2=' . $p2->actual_side);
            }
            return;
        }

        // Now go through all available pairs left and take the one with the minimal deviation
        // from forced faction ratio.

        [$totalGames1, $forcedFaction1] = $this->getForcedStats($ladder, $history, $p1, $forcedId);
        [$totalGames2, $forcedFaction2] = $this->getForcedStats($ladder, $history, $p2, $forcedId);

        Log::debug('applyPolicy1v1: forced stats', [
            'p1' => ['total' => $totalGames1, 'forced' => $forcedFaction1],
            'p2' => ['total' => $totalGames2, 'forced' => $forcedFaction2],
        ]);

        $delta1 = $this->getDelta($totalGames1, $forcedFaction1, $ratio);
        $delta2 = $this->getDelta($totalGames2, $forcedFaction2, $ratio);

        Log::debug('applyPolicy1v1: deltas', [
            'p1_delta' => $delta1,
            'p2_delta' => $delta2,
        ]);

        $bestPair = $currentSides;
        $bestScore = -INF;
        
        foreach ($filteredCandidates as [$a, $b])
        {
            $score = 0.0;

            if ($a === $forcedId && $b !== $forcedId)
            {
                $score = $delta1;
            }
            else if ($a !== $forcedId && $b === $forcedId)
            {
                $score = $delta2;
            }
            else if ($a === $forcedId && $b === $forcedId)
            {
                $score = ($delta1 + $delta2) / 2.0;
            }

            Log::debug('applyPolicy1v1: candidate evaluation', [
                'pair'    => [$a, $b],
                'score'   => $score,
            ]);

            if ($score > $bestScore)
            {
                $bestScore = $score;
                $bestPair = [$a, $b];
                Log::debug('applyPolicy1v1: new bestPair', [
                    'bestPair' => $bestPair,
                    'bestScore'=> $bestScore,
                ]);
            }
        }

        // Apply if required.
        if ($bestPair[0] !== $currentSides[0] || $bestPair[1] !== $currentSides[1])
        {
            $p1->actual_side = $bestPair[0];
            $p2->actual_side = $bestPair[1];
            $p1->save();
            $p2->save();
            Log::info('applyPolicy1v1: forcing p1='. $p1->actual_side . ' and p2=' . $p2->actual_side);
        }
        else
        {
            Log::info('applyPolicy1v1: keeping p1='. $p1->actual_side . ' and p2=' . $p2->actual_side);
        }
    }
}
