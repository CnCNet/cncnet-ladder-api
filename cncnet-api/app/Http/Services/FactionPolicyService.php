<?php

namespace App\Http\Services;

use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\MapPool;
use App\Models\QmMatchPlayer;
use App\Models\QmMap;
use Illuminate\Support\Facades\Log;

class FactionPolicyService
{
    protected function getForcedStats(Ladder $ladder, LadderHistory $history, QmMatchPlayer $player, int $forcedId): array
    {
        $stats = QmMatchPlayer::query()
            ->where('player_id', $player->player_id)
            ->whereHas('qmMatch', function($q) use ($ladder, $history) {
                $q->where('ladder_id', $ladder->id)
                  ->whereBetween('created_at', [$history->start_date, $history->end_date]);
            })
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN actual_side = ? THEN 1 ELSE 0 END) as forced_cnt', [$forcedId])
            ->first();

        $total = (int)($stats->total ?? 0);
        $forcedCnt = (int)($stats->forced_cnt ?? 0);

        return [$total, $forcedCnt];
    }

    protected function createCandidates(array $currentSides, MapPool $pool): array
    {
        [$s1, $s2] = $currentSides;
        $forcedId  = (int)$pool->forced_faction_id;

        // Collect candidates.
        $candidates = [ [$s1, $s2] ];
        $candidates[] = [ $forcedId, $s2 ];
        $candidates[] = [ $s1,       $forcedId ];
        $candidates[] = [ $forcedId, $forcedId ];
        
        // Remove forbidden pairs.
        $valid = [];
        foreach ($candidates as [$faction1, $faction2])
        {
            if (!$pool->isValidPair($faction1, $faction2))
            {
                continue;
            }
            $valid[] = [$faction1, $faction2];
        }

        return $valid;
    }

    public function applyPolicy1v1(MapPool $pool, Ladder $ladder, LadderHistory $history, QmMap $qmMap, QmMatchPlayer $p1, QmMatchPlayer $p2): void
    {
        $forcedId = $pool->forced_faction_id !== null ? (int)$pool->forced_faction_id : null;
        $ratio = $pool->forced_faction_ratio !== null ? (float)$pool->forced_faction_ratio : null;
        
        if ($forcedId === null || $ratio === null)
        {
            // No existing policy.
            return;
        }

        $currentSides = [$p1->actual_side, $p2->actual_side];

        if ($currentSides[0] < 0 || $currentSides[1] < 0)
        {
            Log::warning('applyPolicy1v1: random side encountered, skipping. p1='.$currentSides[0].' p2='.$currentSides[1]);
            return;
        }

        $candidates = $this->createCandidates($currentSides, $pool);

        // Now check which candidates are actually allowed for the map.
        $allowedSides = array_values(array_filter($qmMap->sides_array(), fn ($s) => $s >= 0));

        $filteredCandidates = [];
        foreach ($candidates as [$faction1, $faction2])
        {
            if (!in_array($faction1, $allowedSides, true) || !in_array($faction2, $allowedSides, true))
            {
                // Candidates contain factions, which are not allowed on this map.
                continue;
            }
            $filteredCandidates[] = [$faction1, $faction2];
        }

        if (empty($filteredCandidates))
        {
            Log::info('applyPolicy1v1: cannot apply faction policy on map ' . $qmMap->description);
            return;
        }
        
        if (count($filteredCandidates) === 1)
        {
            [$faction1, $faction2] = $filteredCandidates[0];
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

        $bestPair = $currentSides;
        $lowestForcedRatioDeviation = INF;
        $bestChanges = PHP_INT_MAX;
        
        foreach ($filteredCandidates as [$a, $b])
        {
            $err = 0.5;
            
            if ($a === $forcedId && $b !== $forcedId)
            {
                $err = abs((($forcedFaction1 + 1) / ($totalGames1 + 1)) - $ratio);
            }
            else if ($a !== $forcedId && $b === $forcedId)
            {
                $err = abs((($forcedFaction2 + 1) / ($totalGames2 + 1)) - $ratio);
            }
            else if ($a === $forcedId && $b === $forcedId)
            {
                $err1 = abs((($forcedFaction1 + 1) / ($totalGames1 + 1)) - $ratio);
                $err2 = abs((($forcedFaction2 + 1) / ($totalGames2 + 1)) - $ratio);
                $err = ($err1 + $err2) / 2.0;
            }
            
            $changes = (int)($a !== $currentSides[0]) + (int)($b !== $currentSides[1]);

            if ($err < $lowestForcedRatioDeviation || ($err == $lowestForcedRatioDeviation && $changes < $bestChanges))
            {
                $lowestForcedRatioDeviation = $err;
                $bestChanges = $changes;
                $bestPair = [$a, $b];
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
