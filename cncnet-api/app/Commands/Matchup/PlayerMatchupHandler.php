<?php

namespace App\Commands\Matchup;

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
    public function matchup()
    {
        $ladder = $this->history->ladder;
        $ladderRules = $ladder->qmLadderRules;

        $currentUser = $this->qmPlayer->player->user;
        $currentUserSettings = $currentUser->userSettings;
        $currentPlayer = $this->qmPlayer->player;
        $currentPlayerRank = $currentPlayer->rank($this->history);

        # Fetch all opponents who are currently in queue for this ladder
        $opponentQmQueueEntries = QmQueueEntry::where('qm_match_player_id', '<>', $this->qmQueueEntry->qmPlayer->id)
            ->where('ladder_history_id', '=', $this->history->id)
            ->get();

        # Collection of qm opponents who are within point filter but also includes opponents who have mutual point filter disabled
        $opponentQmQueueEntriesFiltered = (new QmQueueEntry())->newCollection();

        # Check and set that we're an observer
        if ($this->qmPlayer->isObserver())
        {
            $this->matchHasObservers = true;
        }

        foreach ($opponentQmQueueEntries as $opponentEntry)
        {
            $oppQmPlayer = $opponentEntry->qmPlayer;
            $oppUser = $oppQmPlayer->player->user;
            $oppUserSettings = $oppUser->userSettings;
            $oppPlayer = $oppQmPlayer->player;
            $oppPlayerRank = $oppPlayer->rank($this->history);
            $oppUserTier = $oppUser->getUserLadderTier($this->history->ladder)->tier;

            # Checks players are in same league tier otherwise skip
            if ($oppUserTier !== $this->currentUserTier)
            {
                # At this point we've now deemed they can't match based on current tiers/elo
                # But now check if players we've specifically chosen in the admin panel can still match in this tier
                $canMatch = false;

                # Check both as either player could be tier 1
                if ($oppUserTier == 1)
                {
                    $canMatch = $oppUser->canUserPlayBothTiers($ladder);
                }

                if ($canMatch == false && $this->currentUserTier == 1)
                {
                    $canMatch = $currentUser->canUserPlayBothTiers($ladder);
                }

                if ($canMatch == false)
                {
                    Log::info("PlayerMatchupHandler ** Players in different tiers for ladder " . $this->history->ladder->abbreviation
                        . "- P1:" . $oppPlayer->username . " (Tier: " . $oppUserTier . ") VS  P2:"
                        . $currentPlayer->username . " (Tier: " . $this->currentUserTier . ")");
                    continue;
                }
                else
                {
                    Log::info("PlayerMatchupHandler ** Players in different tiers for ladder BUT LeaguePlayer Settings have ruled them to play  "
                        . $this->history->ladder->abbreviation . "- P1:" . $oppPlayer->username . " (Tier: " . $oppUserTier . ") VS  P2:"
                        . $currentPlayer->username . " (Tier: " . $this->currentUserTier . ")");
                }
            }

            $ptFilterOff = false;

            # Checks players point filter settings
            if ($currentUserSettings->disabledPointFilter && $oppUserSettings->disabledPointFilter)
            {
                # Do both players' rank meet the minimum rank required for no pt filter to apply
                $rankDiff = abs($currentPlayerRank - $oppPlayerRank);
                if ($rankDiff <= $ladderRules->point_filter_rank_threshold)
                {
                    $ptFilterOff = true;
                }
            }

            if ($oppQmPlayer->isObserver())
            {
                $opponentQmQueueEntriesFiltered->add($opponentEntry);
                $this->matchHasObservers = true;
            }
            else
            {
                if ($ptFilterOff)
                {
                    # Both players have the point filter disabled, we will ignore the point filter
                    $opponentQmQueueEntriesFiltered->add($opponentEntry);
                }
                else
                {
                    # (updated_at - created_at) / 60 = seconds duration player has been waiting in queue
                    $pointsTime = ((strtotime($this->qmQueueEntry->updated_at) - strtotime($this->qmQueueEntry->created_at))) * $ladderRules->points_per_second;

                    # is the opponent within the point filter
                    if ($pointsTime + $ladderRules->max_points_difference > ABS($this->qmQueueEntry->points - $opponentEntry->points))
                    {
                        $opponentQmQueueEntriesFiltered->add($opponentEntry);
                    }
                }
            }
        }

        $qmOpns = $opponentQmQueueEntriesFiltered->shuffle();
        $totalPlayersInMatch = $ladderRules->player_count - 1;

        # Check we have observers present
        if ($this->matchHasObservers)
        {
            $totalPlayersInMatch = $totalPlayersInMatch + 1;
        }

        if ($qmOpns->count() >= $totalPlayersInMatch)
        {
            // Randomly choose the opponents from the best matches. To prevent
            // long runs of identical matchups.
            $qmOpns = $qmOpns->shuffle()->take($totalPlayersInMatch);

            // Randomly select a map
            $commonQMMaps = array();
            $qmMaps = $ladder->mapPool->maps;

            foreach ($qmMaps as $qmMap)
            {
                $match = true;

                if (
                    array_key_exists(
                        $qmMap->bit_idx,
                        $this->qmPlayer->map_side_array()
                    )
                    && $this->qmPlayer->map_side_array()[$qmMap->bit_idx] > -2
                    &&
                    in_array(
                        $this->qmPlayer->map_side_array()[$qmMap->bit_idx],
                        $qmMap->sides_array()
                    )
                )
                {
                    foreach ($qmOpns as $qOpn)
                    {
                        $opn = $qOpn->qmPlayer;

                        if ($opn === null)
                        {
                            $qOpn->delete();
                            $this->removeQueueEntry();
                            return;
                        }

                        if (
                            array_key_exists($qmMap->bit_idx, $opn->map_side_array())
                            &&
                            ($opn->map_side_array()[$qmMap->bit_idx] < -1 || !in_array(
                                $opn->map_side_array()[$qmMap->bit_idx],
                                $qmMap->sides_array()
                            ))
                        )
                        {
                            $match = false;
                        }
                    }
                }
                else
                {
                    $match = false;
                }

                if ($match)
                {
                    $commonQMMaps[] = $qmMap;
                }
            }

            $reduceMapRepeats = $ladder->qmLadderRules->reduce_map_repeats;

            if ($reduceMapRepeats > 0) //remove the recent maps from common_qm_maps
            {
                $playerGameReports = $currentPlayer->playerGames()
                    ->where("ladder_history_id", "=", $this->history->id)
                    ->where("disconnected", "=", 0)
                    ->where("no_completion", "=", 0)
                    ->where("draw", "=", 0)
                    ->orderBy('created_at', 'DESC')
                    ->limit($reduceMapRepeats)
                    ->get();

                $recentMaps = $playerGameReports->map(function ($item)
                {
                    return $item->game->map;
                });

                $recentMaps = $recentMaps->filter(function ($value)
                {
                    return !is_null($value);
                });

                foreach ($recentMaps as $recentMap)
                {
                    $commonQMMaps = $this->removeMap($recentMap, $commonQMMaps);
                }

                foreach ($qmOpns as $qOpn)
                {
                    $oppPlayer = $qOpn->qmPlayer->player;
                    $oppPlayerGames = $oppPlayer->playerGames()
                        ->where("ladder_history_id", "=", $this->history->id)
                        ->where("disconnected", "=", 0)
                        ->where("no_completion", "=", 0)
                        ->where("draw", "=", 0)
                        ->orderBy('created_at', 'DESC')
                        ->limit($reduceMapRepeats)
                        ->get();

                    $recentMaps = $oppPlayerGames->map(function ($item)
                    {
                        return $item->game->map;
                    });

                    $recentMaps = $recentMaps->filter(function ($value)
                    {
                        return !is_null($value);
                    });

                    foreach ($recentMaps as $recentMap) //remove the opponent's recent maps from common_qm_maps
                    {
                        $commonQMMaps = $this->removeMap($recentMap, $commonQMMaps);
                    }
                }
            }

            if (count($commonQMMaps) < 1)
            {
                Log::info("FindOpponent ** No common maps available");

                $this->qmPlayer->touch();
                return;
            }

            Log::info("FindOpponent ** Players for match: " . count($qmOpns) . " / " . $totalPlayersInMatch);

            if (count($qmOpns) !== $totalPlayersInMatch)
            {
                Log::info("FindOpponent ** Not enough players for match yet");

                $this->qmPlayer->touch();
                return;
            }

            return $this->createMatch(
                $commonQMMaps,
                $qmOpns
            );
        }
    }


    /**
     * Remove this 'Map' from this array of 'QmMaps'.
     * The function will loop through the array of common_qm_maps and check if equal to the $recentmMap
     * @param mixed $recentMap 
     * @param mixed $commonQmMaps 
     * @return array 
     */
    public function removeMap($recentMap, $commonQmMaps)
    {
        $newCommonQmMaps = [];

        foreach ($commonQmMaps as $common_qm_map)
        {
            if ($common_qm_map->map->hash != $recentMap->hash) //only include maps whose map id is not a recent map id
            {
                $newCommonQmMaps[] = $common_qm_map;
            }
            else
            {
                // Log::info("FindOpponent ** Skipping map from being selected: $recentMap");
            }
        }

        if ($commonQmMaps == $newCommonQmMaps)
        {
            // Log::info("FindOpponent ** $recentMap was not found in commonQmMaps");
        }

        return $newCommonQmMaps;
    }
}
