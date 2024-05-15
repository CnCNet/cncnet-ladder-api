<?php

namespace App\Extensions\Qm\Matchup;

use App\Models\Game;
use App\Models\QmQueueEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TeamMatchupHandler extends BaseMatchupHandler
{

    public function matchup() : void
    {
        $ladder = $this->history->ladder;
        $ladderRules = $ladder->qmLadderRules;

        // Check if current player is an observer
        if ($this->qmPlayer->isObserver()) {
            // If yes, then we skip the matchup because we don't want to compare
            // observer with other actual players to find a match.
            // Observer will be added to the match later on.
            return;
        }

        // Fetch all other players in the queue
        $queueEntries = $this->quickMatchService->fetchQmQueueEntry($this->history, $this->qmQueueEntry);
        Log::info("FindOpponent ** players in q : " . $queueEntries->count() + 1);

        // Find opponents that can be matched with current player. Exclude observers
        $matchableOpponents = $this->quickMatchService
            ->getMatchableOpponents($this->qmQueueEntry, $queueEntries)
            ->filter(fn(QmQueueEntry $qmQueueEntry) => !$qmQueueEntry->qmPlayer->isObserver());

        // Count the number of players we need to start a match
        // Excluding current player
        $numberOfOpponentsNeeded = $ladderRules->player_count - 1;

        // Check if there is enough opponents
        if ($matchableOpponents->count() < $numberOfOpponentsNeeded) {
            Log::info("FindOpponent ** Team matchup handler ** Not enough players for match yet");
            $this->qmPlayer->touch();
            return;
        }

        [$teamAPlayers, $teamBPlayers] = $this->quickMatchService->getBestMatch2v2ForPlayer(
            $this->qmQueueEntry,
            $matchableOpponents,
            $this->history
        );

        Log::info("FindOpponent ** TEAMS : "
            . json_encode($teamAPlayers) . ' VS'
            . json_encode($teamBPlayers));

        $players = $teamAPlayers->merge($teamBPlayers);

        $commonQmMaps = $this->quickMatchService->getCommonMapsForPlayers($ladder, $players);

        if (count($commonQmMaps) < 1) {
            Log::info("FindOpponent ** No common maps available");
            $this->qmPlayer->touch();
            return;
        }

        $observers = $queueEntries->filter(fn(QmQueueEntry $qmQueueEntry) => $qmQueueEntry->qmPlayer->isObserver());
        if($observers->count() < 0) {
            $this->matchHasObservers = true;
        }

        $this->createTeamMatch($commonQmMaps, $teamAPlayers, $teamBPlayers, $observers);
    }

    private function createTeamMatch(Collection $maps, Collection $teamAPlayers, Collection $teamBPlayers, Collection $observers) {

        // filter out placeholder maps
        $filteredMaps = $maps->filter(function ($map) {
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
            Game::GAME_TYPE_2VS2
        );
    }
}