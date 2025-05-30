<?php

namespace App\Extensions\Qm\Matchup;

use App\Models\Game;
use App\Models\QmQueueEntry;
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
        $matchableOpponents = $this->quickMatchService->getEntriesInPointRange($this->qmQueueEntry, $matchableOpponents);
      
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
        if ($observers->count() < 0)
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
}
