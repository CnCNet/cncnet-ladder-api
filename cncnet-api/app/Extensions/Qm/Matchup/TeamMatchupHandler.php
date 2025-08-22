<?php

namespace App\Extensions\Qm\Matchup;

use App\Models\Game;
use App\Models\QmQueueEntry;
use App\Models\QmLadderRules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TeamMatchupHandler extends BaseMatchupHandler
{

    public function matchup(): void
    {
        $ladder = $this->history->ladder;
        $ladderRules = $ladder->qmLadderRules;
        $playerInQueue = $this->qmPlayer?->player?->username; // Null-safe evaluation

        // Check if current player is an observer
        if ($this->qmPlayer->isObserver())
        {
            // If yes, then we skip the matchup because we don't want to compare
            // observer with other actual players to find a match.
            // Observer will be added to the match later on.
            return;
        }

        // Fetch all other players in the queue
        $opponents = $this->quickMatchService->fetchQmQueueEntry($this->history, $this->qmQueueEntry);
        $count = $opponents->count() + 1;
        $timeInQueue = $this->qmQueueEntry->secondsinQueue();
        Log::debug("FindOpponent ** inQueue={$playerInQueue}, players in q: {$count}, for ladder={$ladder->abbreviation}, seconds in Queue: {$timeInQueue}");

        // Find opponents in same tier with current player.
        $matchableOpponents = $this->quickMatchService->getEntriesInSameTier($ladder, $this->qmQueueEntry, $opponents);

        // Find opponents that can be matched with current player.
        $matchableOpponents = $this->getEntriesInPointRange2v2($this->qmQueueEntry, $matchableOpponents);

        $opponentCount = $matchableOpponents->count();
        Log::debug("FindOpponent ** inQueue={$playerInQueue}, amount of matchable opponent after point filter: {$opponentCount} of {$count}");

        // Count the number of players we need to start a match
        // Excluding current player
        $numberOfOpponentsNeeded = $ladderRules->player_count - 1;

        // Check if there is enough opponents
        $matchableOpponentsCount = $matchableOpponents->count();
        if ($matchableOpponentsCount < $numberOfOpponentsNeeded)
        {
            Log::debug("FindOpponent ** inQueue={$playerInQueue}, Team matchup handler ** Not enough players for match yet ($matchableOpponentsCount of $numberOfOpponentsNeeded)");
            $this->qmPlayer->touch();
            return;
        }

        [$teamAPlayers, $teamBPlayers, $stats] = $this->quickMatchService->getBestMatch2v2ForPlayer(
            $this->qmQueueEntry,
            $matchableOpponents,
            $this->history
        );

        Log::debug("FindOpponent ** TEAMS : "
            . json_encode($teamAPlayers) . ' VS'
            . json_encode($teamBPlayers));

        // Log player names and their team
        $teamALog = $teamAPlayers->map(function($entry) {
            return $entry->qmPlayer?->player?->username ?? 'Unknown';
        })->implode(', ');
        $teamBLog = $teamBPlayers->map(function($entry) {
            return $entry->qmPlayer?->player?->username ?? 'Unknown';
        })->implode(', ');
        Log::debug("Team A (" . $teamAPlayers->count() . "): " . $teamALog);
        Log::debug("Team B (" . $teamBPlayers->count() . "): " . $teamBLog);

        $players = $teamAPlayers->merge($teamBPlayers);

        $commonQmMaps = $this->quickMatchService->getCommonMapsForPlayers($ladder, $players);

        if (count($commonQmMaps) < 1)
        {
            Log::info("FindOpponent ** No common maps available");
            $this->qmPlayer->touch();
            return;
        }

        // Add observers to the match if there is any
        $observers = $opponents->filter(fn(QmQueueEntry $qmQueueEntry) => $qmQueueEntry->qmPlayer?->isObserver());
        if ($observers->count() > 0)
        {
            $this->matchHasObservers = true;
        }

        // Start the match with all other players and other observers if there is any
        $this->createTeamMatch($commonQmMaps, $teamAPlayers, $teamBPlayers, $observers, $stats);
    }

    private function createTeamMatch(Collection $maps, Collection $teamAPlayers, Collection $teamBPlayers, Collection $observers, array $stats)
    {

        // filter out placeholder maps
        $filteredMaps = $maps->filter(function ($map)
        {
            return
                !strpos($map->description, 'Map Info')
                && !strpos($map->description, 'Map Guide')
                && !strpos($map->description, 'Ladder Guide')
                && !strpos($map->description, 'Ladder Rules');
        });

        $this->quickMatchService->createTeamQmMatch(
            $this->history,
            $filteredMaps,
            $teamAPlayers,
            $teamBPlayers,
            $observers,
            Game::GAME_TYPE_2VS2,
            $stats
        );
    }

    /**
     * Attempt to find a valid 2v2 match for the given player.
     * Ensures that:
     * - The current player is always included in the match.
     * - All players in the match are within each other's allowed point range.
     * - The result is sorted by closeness in point range to improve match fairness.
     *
     * @param QmQueueEntry $currentQmQueueEntry
     * @param Collection|QmQueueEntry[] $opponents
     * @return Collection|QmQueueEntry[]  A collection containing the matched players (including current), or empty if no match found
     */
    public function getEntriesInPointRange2v2(QmQueueEntry $currentQmQueueEntry, Collection $opponents): Collection
    {
        $rules = $currentQmQueueEntry->ladderHistory->ladder->qmLadderRules;
        $playerName = $currentQmQueueEntry->qmPlayer?->player?->username ?? 'Unknown';

        Log::debug("2v2 Search start: queueEntry={$currentQmQueueEntry->id}, name={$playerName}, opponentsInQueue=" . count($opponents));

        // Filter opponents to those that pass point range check with the current player
        $potentialOpponents = $this->filterOpponentsInRange($currentQmQueueEntry, $opponents, $rules)
            ->filter(function($opponent) use ($currentQmQueueEntry) {
                return $opponent->id !== $currentQmQueueEntry->id;
            })->values();

        Log::debug($potentialOpponents->count() . " potential opponents for {$playerName}: " . $potentialOpponents->pluck('qmPlayer.player.username')->implode(', '));

        if ($potentialOpponents->count() < 3)
        {
            Log::debug("Not enough valid opponents for {$playerName} to form a 2v2 match: " . $potentialOpponents->count() . "/3");
            return collect();
        }

        // Sort opponents by closeness in points to the current player
        $sortedOpponents = $potentialOpponents->sortBy(function ($opponent) use ($currentQmQueueEntry)
        {
            return abs($currentQmQueueEntry->points - $opponent->points);
        })->values();

        // Try all possible 3-player combinations (since the current player makes 4)
        foreach ($this->getCombinations($sortedOpponents, 3) as $threeOthers)
        {
            $matchPlayers = collect([$currentQmQueueEntry])->merge($threeOthers)->unique('id')->values();

            if ($matchPlayers->count() !== 4) {
                continue;
            }

            if ($this->allPlayersInRange($matchPlayers, $rules))
            {
                Log::debug(
                    "✅ Found valid 2v2 match for {$playerName}: " .
                        $matchPlayers->pluck('qmPlayer.player.username')->implode(', ')
                );
                return $matchPlayers;
            }
        }

        Log::debug("❌ No valid 2v2 match found for {$playerName}");
        return collect();
    }

    /**
     * Generate combinations of a given size from a collection.
     *
     * @param Collection $items
     * @param int $size
     * @return Collection
     */
    private function getCombinations(Collection $items, int $size): Collection
    {
        $array = $items->all();
        $results = [];

        $recurse = function ($arr, $size, $start = 0, $current = []) use (&$results, &$recurse)
        {
            if (count($current) === $size)
            {
                $results[] = $current;
                return;
            }
            for ($i = $start; $i < count($arr); $i++)
            {
                $current[] = $arr[$i];
                $recurse($arr, $size, $i + 1, $current);
                array_pop($current);
            }
        };

        $recurse($array, $size);

        return collect($results);
    }

    /**
     * Filter opponents that are within point range of the current player.
     *
     * @param QmQueueEntry $current
     * @param Collection|QmQueueEntry[] $opponents
     * @param QmLadderRules $rules
     * @return Collection|QmQueueEntry[]
     */
    private function filterOpponentsInRange(QmQueueEntry $current, Collection $opponents, QmLadderRules $rules): Collection
    {
        $pointsPerSecond = $rules->points_per_second;
        $maxPointsDifference = $rules->max_points_difference;
        $currentPointFilter = $current->qmPlayer->player->user->userSettings->disabledPointFilter;

        $matchable = collect();

        foreach ($opponents as $opponent)
        {
            if (!isset($opponent->qmPlayer) || $opponent->qmPlayer->isObserver())
            {
                continue;
            }

            $diff = abs($current->points - $opponent->points);
            $waitTimeBonus = (strtotime($current->updated_at) - strtotime($current->created_at)) * $pointsPerSecond;

            $inNormalRange = $waitTimeBonus + $maxPointsDifference > $diff;
            $inDisabledFilterRange = $currentPointFilter
                && $opponent->qmPlayer->player->user->userSettings->disabledPointFilter
                && $diff < 1000
                && $current->points > 400
                && $opponent->points > 400;

            if ($inNormalRange || $inDisabledFilterRange)
            {
                $matchable->push($opponent);
            }
        }

        return $matchable;
    }

    /**
     * Check if all players in the given collection are within point range of each other.
     * Logs a pass/fail table for each comparison.
     *
     * @param Collection|QmQueueEntry[] $players
     * @param QmLadderRules $rules
     * @return bool
     */
    private function allPlayersInRange(Collection $players, QmLadderRules $rules): bool
    {
        $pointsPerSecond = $rules->points_per_second;
        $maxPointsDifference = $rules->max_points_difference;
        $comparisonResults = [];

        foreach ($players as $i => $p1)
        {
            foreach ($players as $j => $p2)
            {
                if ($i >= $j) continue; // Skip self and duplicate checks

                $diff = abs($p1->points - $p2->points);
                $waitTimeBonusP1 = (strtotime($p1->updated_at) - strtotime($p1->created_at)) * $pointsPerSecond;
                $waitTimeBonusP2 = (strtotime($p2->updated_at) - strtotime($p2->created_at)) * $pointsPerSecond;

                $passesNormalRange = ($waitTimeBonusP1 + $maxPointsDifference >= $diff)
                    || ($waitTimeBonusP2 + $maxPointsDifference >= $diff);

                $passesDisabledFilter = $p1->qmPlayer->player->user->userSettings->disabledPointFilter
                    && $p2->qmPlayer->player->user->userSettings->disabledPointFilter
                    && $diff < 1000
                    && $p1->points > 400
                    && $p2->points > 400;

                $pass = $passesNormalRange || $passesDisabledFilter;

                $comparisonResults[] = [
                    'p1' => $p1->qmPlayer?->player?->username ?? 'Unknown',
                    'p2' => $p2->qmPlayer?->player?->username ?? 'Unknown',
                    'points1' => $p1->points,
                    'points2' => $p2->points,
                    'diff' => $diff,
                    'pass' => $pass
                ];

                if (!$pass)
                {
                    $this->logComparisonTable($comparisonResults);
                    return false;
                }
            }
        }

        $this->logComparisonTable($comparisonResults);
        return true;
    }

    /**
     * Logs the comparison results for all player pairs.
     *
     * @param array $comparisonResults
     */
    private function logComparisonTable(array $comparisonResults): void
    {
        Log::debug("=== 2v2 Player Comparison Table ===");
        foreach ($comparisonResults as $result)
        {
            $status = $result['pass'] ? '✅ PASS' : '❌ FAIL';
            Log::debug(sprintf(
                "%-15s (%4d pts) ↔ %-15s (%4d pts) | Diff: %4d | %s",
                $result['p1'],
                $result['points1'],
                $result['p2'],
                $result['points2'],
                $result['diff'],
                $status
            ));
        }
        Log::debug("===================================");
    }
}
