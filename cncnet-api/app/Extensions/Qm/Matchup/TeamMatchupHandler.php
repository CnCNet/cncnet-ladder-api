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
            Log::debug("TeamMatchup ** {$playerInQueue} is an observer, skipping matchup process. Will be added to match when other players form one.");
            return;
        }

        // Fetch all other players in the queue and exclude current player
        $opponents = $this->quickMatchService->fetchQmQueueEntry($this->history, $this->qmQueueEntry)
            ->filter(function($entry) {
                return $entry->id !== $this->qmQueueEntry->id;
            })->values();

        $totalInQueue = $opponents->count() + 1;
        $timeInQueue = $this->qmQueueEntry->secondsinQueue();
        $waitTimeBonus = $timeInQueue * $ladderRules->points_per_second;
        $effectiveRange = $ladderRules->max_points_difference + $waitTimeBonus;

        Log::info("=== TeamMatchup START for {$playerInQueue} ===");
        Log::info("  Player: {$playerInQueue} | Points: {$this->qmQueueEntry->points} | Ladder: {$ladder->abbreviation}");
        Log::info("  Time in queue: {$timeInQueue}s | Wait bonus: +{$waitTimeBonus} pts | Effective range: {$effectiveRange} pts");
        Log::info("  Total players in queue: {$totalInQueue} (including {$playerInQueue})");

        $allPlayerNames = $opponents->map(fn($e) => $e->qmPlayer?->player?->username ?? 'Unknown')->implode(', ');
        Log::debug("  All players in queue: {$playerInQueue}, {$allPlayerNames}");

        // Find opponents in same tier with current player.
        $beforeTierFilter = $opponents->count();
        $matchableOpponents = $this->quickMatchService->getEntriesInSameTier($ladder, $this->qmQueueEntry, $opponents);
        $afterTierFilter = $matchableOpponents->count();

        if ($afterTierFilter < $beforeTierFilter) {
            $filtered = $beforeTierFilter - $afterTierFilter;
            Log::debug("  Tier filter: {$filtered} players filtered out ({$afterTierFilter} remain in same tier)");
        }

        // Find opponents that can be matched with current player based on points.
        $matchableOpponents = $this->getEntriesInPointRange2v2($this->qmQueueEntry, $matchableOpponents);

        $opponentCount = $matchableOpponents->count();
        $matchableNames = $matchableOpponents->map(function($entry) {
            return $entry->qmPlayer?->player?->username ?? 'Unknown';
        })->implode(', ');

        if ($opponentCount > 0) {
            Log::info("  ✅ Point filter passed: {$opponentCount} matchable opponents found");
            Log::debug("     Matchable players: [{$matchableNames}]");
        } else {
            Log::info("  ❌ Point filter: No matchable opponents found after point range validation");
        }

        // Count the number of players we need to start a match
        // Excluding current player
        $numberOfOpponentsNeeded = $ladderRules->player_count - 1;

        // Check if there is enough opponents
        $matchableOpponentsCount = $matchableOpponents->count();
        if ($matchableOpponentsCount < $numberOfOpponentsNeeded)
        {
            Log::info("  ❌ MATCH FAILED: Not enough players ({$matchableOpponentsCount}/{$numberOfOpponentsNeeded} needed)");
            Log::info("=== TeamMatchup END for {$playerInQueue} - NO MATCH ===\n");
            return;
        }

        Log::debug("  ✅ Sufficient players found, attempting to form teams...");

        [$teamAPlayers, $teamBPlayers, $stats] = $this->quickMatchService->getBestMatch2v2ForPlayer(
            $this->qmQueueEntry,
            $matchableOpponents,
            $this->history
        );

        // Log player names and their team
        $teamALog = $teamAPlayers->map(function($entry) {
            $pts = $entry->points;
            $name = $entry->qmPlayer?->player?->username ?? 'Unknown';
            return "{$name} ({$pts} pts)";
        })->implode(', ');
        $teamBLog = $teamBPlayers->map(function($entry) {
            $pts = $entry->points;
            $name = $entry->qmPlayer?->player?->username ?? 'Unknown';
            return "{$name} ({$pts} pts)";
        })->implode(', ');

        Log::info("  Teams formed:");
        Log::info("    Team A ({$teamAPlayers->count()}): {$teamALog}");
        Log::info("    Team B ({$teamBPlayers->count()}): {$teamBLog}");

        // Ensure both teams have exactly two players
        if ($teamAPlayers->count() !== 2 || $teamBPlayers->count() !== 2) {
            Log::warning("  ⚠️  Team size error: Team A has {$teamAPlayers->count()} players, Team B has {$teamBPlayers->count()} players. Expected 2 each.");
        }

        $players = $teamAPlayers->merge($teamBPlayers);

        $commonQmMaps = $this->quickMatchService->getCommonMapsForPlayers($ladder, $players);
        $mapCount = count($commonQmMaps);

        if ($mapCount < 1)
        {
            Log::info("  ❌ MATCH FAILED: No common maps available between all players");
            Log::info("=== TeamMatchup END for {$playerInQueue} - NO MATCH ===\n");
            return;
        }

        Log::debug("  ✅ Map validation: {$mapCount} common maps available");

        // Add observers to the match if there is any (maximum of one observer per match)
        // Prioritize observers who have been waiting the longest
        $allObservers = $opponents->filter(fn(QmQueueEntry $qmQueueEntry) => $qmQueueEntry->qmPlayer?->isObserver());
        $observers = $allObservers->sortBy('created_at')->take(1);

        if ($observers->count() > 0) {
            $this->matchHasObservers = true;
            $observerName = $observers->first()->qmPlayer?->player?->username ?? 'Unknown';
            $observerWait = $observers->first()->secondsinQueue();
            Log::info("  👁  Observer added: {$observerName} (waited {$observerWait}s)");

            if ($allObservers->count() > 1) {
                $remainingCount = $allObservers->count() - 1;
                Log::debug("     {$remainingCount} other observer(s) remain in queue");
            }
        } else {
            Log::debug("  No observers in queue");
        }

        // Throw exception if team sizes are not exactly two
        if ($teamAPlayers->count() !== 2 || $teamBPlayers->count() !== 2) {
            Log::error("  ❌ MATCH FAILED: Invalid team sizes - Team A: {$teamAPlayers->count()}, Team B: {$teamBPlayers->count()}");
            throw new \RuntimeException("Team size error: Team A has {$teamAPlayers->count()} players, Team B has {$teamBPlayers->count()} players. Expected 2 each.");
        }

        Log::info("  ✅ MATCH CREATED: All validations passed, creating match...");
        Log::info("=== TeamMatchup END for {$playerInQueue} - MATCH CREATED ===\n");

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
        $playerPoints = $currentQmQueueEntry->points;
        $playerWaitTime = $currentQmQueueEntry->secondsinQueue();
        $waitBonus = $playerWaitTime * $rules->points_per_second;

        Log::debug("  --- 2v2 Point Range Search for {$playerName} ({$playerPoints} pts, +{$waitBonus} wait bonus) ---");

        // Filter opponents to those that pass point range check with the current player
        $potentialOpponents = $this->filterOpponentsInRange($currentQmQueueEntry, $opponents, $rules)
            ->filter(function($opponent) use ($currentQmQueueEntry) {
                return $opponent->id !== $currentQmQueueEntry->id;
            })->values();

        $potentialCount = $potentialOpponents->count();
        $potentialNames = $potentialOpponents->map(function($e) {
            return $e->qmPlayer?->player?->username . ' (' . $e->points . ' pts)';
        })->implode(', ');

        Log::debug("     Initial filter: {$potentialCount} potential opponents pass 1-on-1 range check");
        if ($potentialCount > 0) {
            Log::debug("     Potential: {$potentialNames}");
        }

        if ($potentialOpponents->count() < 3)
        {
            Log::debug("     ❌ Not enough opponents for 2v2: {$potentialCount}/3 needed");
            return collect();
        }

        // Sort opponents by a combination of point difference and time in queue bonus
        $pointsPerSecond = $rules->points_per_second;
        $sortedOpponents = $potentialOpponents->sortBy(function ($opponent) use ($currentQmQueueEntry, $pointsPerSecond) {
            $pointDiff = abs($currentQmQueueEntry->points - $opponent->points);
            $waitTimeBonus = $opponent->secondsinQueue() * $pointsPerSecond;
            return $pointDiff - $waitTimeBonus;
        })->values();

        Log::debug("     Sorted opponents by closeness (considering wait time)");

        // Try all possible 3-player combinations (since the current player makes 4)
        $combinationsTried = 0;
        foreach ($this->getCombinations($sortedOpponents, 3) as $threeOthers)
        {
            $matchPlayers = collect([$currentQmQueueEntry])->merge($threeOthers)->unique('id')->values();

            if ($matchPlayers->count() !== 4) {
                continue;
            }

            $combinationsTried++;
            $comboNames = $matchPlayers->pluck('qmPlayer.player.username')->implode(', ');

            if ($this->allPlayersInRange($matchPlayers, $rules))
            {
                Log::debug("     ✅ Found valid 2v2 match after trying {$combinationsTried} combination(s)");
                Log::debug("     Match: {$comboNames}");
                return $matchPlayers;
            }
        }

        Log::debug("     ❌ No valid 2v2 match found after trying {$combinationsTried} combination(s)");
        Log::debug("     Reason: All combinations failed cross-player range validation");
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
        $currentPlayer = $current->qmPlayer?->player?->username ?? 'Unknown';

        $matchable = collect();
        $filtered = collect();

        foreach ($opponents as $opponent)
        {
            if (!isset($opponent->qmPlayer) || $opponent->qmPlayer->isObserver())
            {
                continue;
            }

            $opponentName = $opponent->qmPlayer?->player?->username ?? 'Unknown';
            $diff = abs($current->points - $opponent->points);
            $waitTimeBonus = $current->secondsinQueue() * $pointsPerSecond;
            $effectiveRange = $waitTimeBonus + $maxPointsDifference;

            $inNormalRange = $effectiveRange > $diff;
            $inDisabledFilterRange = $currentPointFilter
                && $opponent->qmPlayer->player->user->userSettings->disabledPointFilter
                && $diff < 1000
                && $current->points > 400
                && $opponent->points > 400;

            if ($inNormalRange || $inDisabledFilterRange)
            {
                $matchable->push($opponent);
            }
            else
            {
                $filtered->put($opponentName, [
                    'points' => $opponent->points,
                    'diff' => $diff,
                    'range' => $effectiveRange,
                    'reason' => $diff > $effectiveRange ? 'Points too far' : 'Filter mismatch'
                ]);
            }
        }

        if ($filtered->count() > 0) {
            Log::debug("     Filtered out {$filtered->count()} player(s) outside point range:");
            foreach ($filtered as $name => $info) {
                Log::debug("       - {$name} ({$info['points']} pts): Δ{$info['diff']} > {$info['range']} range");
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
                $waitTimeBonusP1 = $p1->secondsinQueue() * $pointsPerSecond;
                $waitTimeBonusP2 = $p2->secondsinQueue() * $pointsPerSecond;

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
                    'wait1' => $p1->secondsinQueue(),
                    'wait2' => $p2->secondsinQueue(),
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
        Log::debug("     === 2v2 Player Pair Validation ===");
        $passCount = 0;
        $failCount = 0;

        foreach ($comparisonResults as $result)
        {
            $status = $result['pass'] ? '✅' : '❌';
            if ($result['pass']) {
                $passCount++;
            } else {
                $failCount++;
            }

            $waitInfo = isset($result['wait1']) && isset($result['wait2'])
                ? " | Wait: {$result['wait1']}s/{$result['wait2']}s"
                : "";

            Log::debug(sprintf(
                "     %s %-15s (%4d) ↔ %-15s (%4d) | Δ%4d%s",
                $status,
                $result['p1'],
                $result['points1'],
                $result['p2'],
                $result['points2'],
                $result['diff'],
                $waitInfo
            ));
        }

        $total = $passCount + $failCount;
        Log::debug("     Summary: {$passCount}/{$total} pairs passed, {$failCount}/{$total} failed");
        Log::debug("     =====================================");
    }
}
