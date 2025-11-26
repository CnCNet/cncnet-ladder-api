<?php

namespace App\Extensions\Qm\Matchup;

use App\Models\QmQueueEntry;
use Illuminate\Support\Facades\Log;

class PlayerMatchupHandler extends BaseMatchupHandler
{
    /**
     * Try to find a matchup
     * Matchups are based on the player's rating,
     * The absolute value of the difference of me and every other player is calculated.
     * Any players whose difference is greater 100 is thrown out with some exceptions
     * 
     * If a player has been waiting a long time for a matchup he should get some special
     * treatment.  To allow for this, the player rating difference gets wait time, in
     * seconds, subtracted from it.
     * 
     * If 2 players rated 1200, and 1400 are the only players a match won't be made
     * until one player has been waiting for 100 seconds 1400-1200-100seconds = 100
     * The ratio of seconds is tunable per ladder
     */
    public function matchup(): void
    {
        $ladder = $this->history->ladder;
        $ladderRules = $ladder->qmLadderRules;
        $playerName = $this->qmPlayer?->player?->username;

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

        // Find opponents in same tier with current player.
        $matchableOpponents = $this->quickMatchService->getEntriesInSameTier($ladder, $this->qmQueueEntry, $opponents);

        // Find opponents that can be matched with current player.
        $matchableOpponents = $this->quickMatchService->getMatchableOpponents($this->qmQueueEntry, $matchableOpponents)->shuffle();

        // filter out yuri players
        $matchableOpponents = $this->quickMatchService->removeYuriPlayers($this->qmQueueEntry, $matchableOpponents);

        $numberOfOpponentsNeeded = $ladderRules->player_count - 1;

        // Check if there is enough opponents
        if ($matchableOpponents->count() < $numberOfOpponentsNeeded)
        {
            Log::debug("FindOpponent ** Not enough players for match yet, ladder: $ladder->abbreviation, player: $playerName");
            $this->qmPlayer->touch();
            return;
        }

        // Randomly choose the opponents from the best matches.
        // To prevent long runs of identical matchups.
        $matchedOpponents = $matchableOpponents->take($numberOfOpponentsNeeded);

        // Get a collection with all actual players that will be matched together
        $players = $matchedOpponents->concat([$this->qmQueueEntry]);

        // Find maps common to all actual players
        $commonQmMaps = $this->quickMatchService->getCommonMapsForPlayers($ladder, $players);

        // Remove the recent maps from $commonQmMaps if reduce_map_repeats is active
        $mapRepeats = $ladder->qmLadderRules->reduce_map_repeats;
        if ($mapRepeats > 0 && $mapRepeats < $ladder->mapPool->maps->count())
        {
            $commonQmMaps = $this->quickMatchService->filterOutRecentsMaps($this->history, $commonQmMaps, $players);
        }

        if (count($commonQmMaps) < 1)
        {
            Log::info("FindOpponent ** No common maps available for ladder: $ladder->abbreviation, player: $playerName");
            $this->qmPlayer->touch();
            return;
        }

        // Add observers to the match if there is any (maximum of one observer per match)
        // Prioritize observers who have been waiting the longest
        $observers = $opponents
            ->filter(fn (QmQueueEntry $qmQueueEntry) => $qmQueueEntry->qmPlayer->isObserver())
            ->sortBy('created_at')
            ->take(1);
        if ($observers->count() > 0)
        {
            $this->matchHasObservers = true;
            $matchedOpponents = $matchedOpponents->merge($observers);
        }

        // Start the match with all other players and other observers if there is any
        $this->createMatch($commonQmMaps, $matchedOpponents);
    }
}
