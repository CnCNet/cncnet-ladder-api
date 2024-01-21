<?php

namespace App\Commands\Matchup;

use App\QmQueueEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ClanMatchupHandler extends BaseMatchupHandler
{
    public function matchup()
    {
        Log::info("ClanMatchupHandler ** Started");

        // its 2v2 so we need 2 clans
        $numberOfClanRequired = 2;

        $ladder = $this->history->ladder;
        $ladderRules = $ladder->qmLadderRules;
        $ladderMaps = $ladder->mapPool->maps;

        $currentPlayer = $this->qmPlayer->player;
        $playerCountPerClanRequired = floor($ladderRules->player_count / $numberOfClanRequired); # (2) for a 2v2
        $playerCountForMatchup = $ladderRules->player_count; # (4) for a 2v2

        # Fetch all entries who are currently in queue for this ladder
        $allQMQueueEntries = QmQueueEntry::where('ladder_history_id', '=', $this->history->id)->get();

        // get all observers from qm queue entries
        $observersQmQueueEntries = $allQMQueueEntries->filter(function($qmQueueEntry) {
            return $qmQueueEntry->qmPlayer->isObserver();
        });
        $this->matchHasObservers = $observersQmQueueEntries->count() > 0;

        Log::info("ClanMatchupHandler ** Players Per Clan Required: " . $playerCountPerClanRequired);
        Log::info("ClanMatchupHandler ** Players For Matchup Required: " . $playerCountForMatchup);
        Log::info("ClanMatchupHandler ** Match Has Observer Present: " . ($this->matchHasObservers ? 'yes' : 'no'));

        // if a player has no clan, then remove him from the queue
        if (!isset($currentPlayer->clanPlayer))
        {
            Log::info("ClanMatchupHandler ** Clan Player Null, removing $currentPlayer from queue");
            $this->removeQueueEntry();
            return;
        }

        $groupedQmQueueEntriesByClan = $allQMQueueEntries
            // filter out observers
            ->reject(function($qmQueueEntry) {
                return $qmQueueEntry->qmPlayer->isObserver();
            })
            // group all qm queue entries by clan
            ->groupBy(function($qmQueueEntry) {
                return $qmQueueEntry->qmPlayer->clan_id;
            })
            // filter out clans that don't have enough players
            ->reject(function($clanQmQueueEntries) use ($playerCountPerClanRequired) {
                return $clanQmQueueEntries->count() < $playerCountPerClanRequired;
            });

        // now $groupedQmQueueEntriesByClan is a collection of clan with at least 2 players per clan

        // if there is not enough clan ready, then exit
        if($groupedQmQueueEntriesByClan->count() < $numberOfClanRequired)
        {
            Log::info("ClanMatchupHandler ** There is " . $groupedQmQueueEntriesByClan->count() . " clans ready, but we need $numberOfClanRequired clans");
            Log::info("ClanMatchupHandler ** There is " . $allQMQueueEntries->count() . " queue entries");
            Log::info("ClanMatchupHandler ** Not enough clans/players in queue, exiting...");
            return;
        }

        // we need to find the clan that has the current player in it
        $currentPlayerClan = $groupedQmQueueEntriesByClan->filter(function($clanQmQueueEntries) use ($currentPlayer) {
            return $clanQmQueueEntries->filter(function($qmQueueEnitry) use ($currentPlayer) {
                    return $qmQueueEnitry->qmPlayer->clan_id == $currentPlayer->clanPlayer->clan_id;
                })->count() === 1;
        })->take(1);
        $currentPlayerClanClanId = $currentPlayerClan->keys()->first();

        Log::info("ClanMatchupHandler ** Current player clan id: " . $currentPlayerClanClanId);

        // and we need to find $numberOfClanRequired - 1 other clans
        // lets just exclude the currentPlayerClan and remove from the list players that are already in currentPlayerClan
        $otherClans = $groupedQmQueueEntriesByClan
            // remove currentPlayerClan from the collection
            ->reject(function($clanQmQueueEntries, $clanId) use ($currentPlayerClanClanId, $currentPlayerClan) {
                return $clanId == $currentPlayerClanClanId;
            })
            // remove players from currentPlayerClan from other clan
            ->map(function ($clanQmQueueEntries) use ($currentPlayerClan) {
                return $clanQmQueueEntries->reject(function($qmQueueEntry) use ($currentPlayerClan) {
                    return $currentPlayerClan->flatten(1)->pluck('id')->contains($qmQueueEntry->id);
                });
            })
            // remove clans that don't have enough players
            ->reject(function($clanQmQueueEntries) use ($playerCountPerClanRequired) {
                return $clanQmQueueEntries->count() < $playerCountPerClanRequired;
            })
            // take randomly 2 players from clan that have too many players
            ->map(function($clanQmQueueEntries, $clanId) use ($playerCountPerClanRequired) {
                if($clanQmQueueEntries->count() > $playerCountPerClanRequired)
                {
                    Log::info("ClanMatchupHandler ** There is " . $clanQmQueueEntries->count() . " players in clan id = " . $clanId . ", but we need only $playerCountPerClanRequired players");
                    Log::info("ClanMatchupHandler ** Taking $playerCountPerClanRequired players randomly from clan id = " . $clanId);
                    return $clanQmQueueEntries->random($playerCountPerClanRequired);
                }
                return $clanQmQueueEntries;
            })
        ;


        // if there is not enough other clan ready, then exit
        if($otherClans->count() < $numberOfClanRequired -1)
        {
            Log::info("ClanMatchupHandler ** There is " . $otherClans->count() . " other clans ready, but we need " . ($numberOfClanRequired - 1) . " other clans");
            Log::info("ClanMatchupHandler ** Not enough other clans/players in queue, exiting...");
            return;
        }

        // if there is more than $numberOfClanRequired - 1 clan ready, then randomly take $numberOfClanRequired - 1 clans
        if($otherClans->count() > $numberOfClanRequired - 1)
        {
            Log::info("ClanMatchupHandler ** There is " . ($otherClans->count() + 1) . " clans ready, but we need only $numberOfClanRequired clans");
            Log::info("ClanMatchupHandler ** Taking $numberOfClanRequired clans randomly");

            $otherClans = $otherClans->random($numberOfClanRequired - 1);
        }

        // now $groupedQmQueueEntriesByClan is a collection of clan that is ready for matchup
        // and the current player is in one of these clans
        // and all players are unique and in only one clan
        $groupedQmQueueEntriesByClan = $otherClans->put($currentPlayerClanClanId, $currentPlayerClan);

        // get a collection with all players ready (without current player)
        $readyQmQueueEntries = $groupedQmQueueEntriesByClan
            ->flatten(1)
            ->filter(function($qmQueueEntry) use ($currentPlayer) {
                return $qmQueueEntry->qmPlayer->player->id != $currentPlayer->id;
            });

        $playersReadyCount = $readyQmQueueEntries->count() + 1;
        Log::info("ClanMatchUpHandler ** Player count for matchup: Ready: " . $playersReadyCount . "  Required: " . $playerCountForMatchup);
        Log::info("ClanMatchUpHandler ** Observers count for matchup: " . $observersQmQueueEntries->count());

        // find common maps of all players
        $commonQmMaps = $this->removeRejectedMaps($ladderMaps, $this->qmPlayer, $readyQmQueueEntries);

        if (count($commonQmMaps) <= 0)
        {
            Log::info("ClanMatchUpHandler ** 0 commonQmMaps found, exiting...");
        }
        else
        {
            $playerNames = implode(",", $this->getPlayerNamesInQueue($readyQmQueueEntries));
            Log::info("Launching clan match with players $playerNames, " . $currentPlayer->username);
            Log::info("    with oberservers: " . ($this->matchHasObservers ? 'yes' : 'no'));

            // add observers to our ready qm entries so they will be added to the match
            $observersQmQueueEntries->each(function($qmQueueEntry) use ($readyQmQueueEntries) {
                $readyQmQueueEntries->push($qmQueueEntry);
            });

            return $this->createMatch(
                $commonQmMaps,
                $readyQmQueueEntries
            );
        }
    }

    /**
     * Check match for observer
     * @param mixed $qmPlayer 
     * @param mixed $allQMQueueEntries 
     * @return bool 
     */
    private function checkMatchForObserver($allQMQueueEntries)
    {
        $hasObserver = false;

        foreach ($allQMQueueEntries as $qmQueueEntry)
        {
            Log::info("ClanMatchupHandler ** Checking for observer: " . $qmQueueEntry->qmPlayer->player->username . " : " . $qmQueueEntry->qmPlayer->isObserver());

            if ($qmQueueEntry->qmPlayer->isObserver())
            {
                $hasObserver = true;
                break;
            }
        }

        return $hasObserver;
    }

    public static function getPlayerNamesInQueue($readyQMQueueEntries)
    {
        $playerNames = [];

        foreach ($readyQMQueueEntries as $readyQMQueueEntry)
        {
            $playerNames[] = $readyQMQueueEntry->qmPlayer->player->username;
        }

        return $playerNames;
    }

    private function removeRejectedMaps($qmMaps, $currentQmPlayer, $qmEntries)
    {
        $team1 = [];
        $team1[] = $currentQmPlayer;
        $team2 = [];

        //assign other players to correct clan (assumes there are 2 clans)
        foreach ($qmEntries as $qmEntry)
        {
            if ($qmEntry->qmPlayer->id == $currentQmPlayer->id)
                continue;

            if ($qmEntry->qmPlayer->clan_id == $currentQmPlayer->clan_id)
                $team1[] = $qmEntry->qmPlayer;
            else
                $team2[] = $qmEntry->qmPlayer;
        }

        $commonQMMaps = [];

        $allTeams = [];
        $allTeams[] = $team1;
        $allTeams[] = $team2;

        foreach ($qmMaps as $qmMap) # Loop through every qm map in this map pool
        {
            $match = true;
            foreach ($allTeams as $team) # Loop through each team, if every member in team has rejected the map then exclude it
            {
                if (!$match) # map was rejected by a clan
                    break;

                foreach ($team as $qmPlayer) # Loop through each member in the team
                {
                    # If map index exists in qmPlayer side array,
                    # and qmPlayer's side is greater than -2 (-2 = rejected),
                    # and qmPlayer's side is in QmMap sides,
                    # Then add map to commonMaps
                    if (
                        array_key_exists($qmMap->bit_idx, $qmPlayer->map_side_array())
                        && $qmPlayer->map_side_array()[$qmMap->bit_idx] > -2
                        && in_array($qmPlayer->map_side_array()[$qmMap->bit_idx], $qmMap->sides_array())
                    )
                    {
                        $match = true;
                        break; //this map is valid for at least one member of this team, so this map will be added
                    }
                    else
                    {
                        $match = false; //map must be rejected by all members of team to be rejected
                    }
                }
            }

            if ($match) # map was not rejected by either clan
            {
                $commonQMMaps[] = $qmMap;
            }
            else
            {
                Log::info("ClanMatchupHandler.removeRejectedMaps() ** Rejecting QmMap: " . $qmMap->map->name);
            }
        }

        return $commonQMMaps;
    }

    /**
     * Return QM Queue Entries grouped by clan
     * @param mixed $allQMQueueEntries - All queue entries
     * @param mixed $limit - Number of entries per clan
     * @return array 
     */
    private function groupAndLimitClanPlayers($allQMQueueEntries, $limit)
    {
        $result = [];

        # Loop over all QM Queue Entries and group them by clan
        foreach ($allQMQueueEntries as $qmQueueEntry)
        {
            # Don't add observers to clan counts
            if ($qmQueueEntry->qmPlayer->isObserver())
            {
                continue;
            }

            if (isset($result[$qmQueueEntry->qmPlayer->clan_id]))
            {
                $count = count($result[$qmQueueEntry->qmPlayer->clan_id]);

                # We've reached enough players in this clan for a match
                if ($count == $limit)
                {
                    continue;
                }
            }
            $result[$qmQueueEntry->qmPlayer->clan_id][] = $qmQueueEntry;
        }

        return $result;
    }

    /**
     * Remove any clans in queue if the current user has a player who also belongs in that clan
     */
    public function removeClansCurrentPlayerIsIn($currentUserClanPlayer, $ladderId, $groupedQmQueueEntriesByClan)
    {
        $result = [];

        //grab my clan's QM Entries
        $myClansQmEntries = [];
        foreach ($groupedQmQueueEntriesByClan as $clanId => $allQMQueueEntries)
        {
            if ($clanId == $currentUserClanPlayer->clan->id)
            {
                $myClansQmEntries = $allQMQueueEntries;
                $result[$clanId] = $myClansQmEntries;
            }
        }

        //loop through opponent clans, check if any of my clan members in queue are in their clan also
        foreach ($groupedQmQueueEntriesByClan as $opponentClanId => $opponentQmEntries)
        {
            if ($opponentClanId == $currentUserClanPlayer->clan->id) //self
            {
                continue;
            }

            $canMatch = true;
            foreach ($myClansQmEntries as $qmEntry) //loop through the members in my clan, check if a player in my clan is also in opponent's clan
            {
                $players = $qmEntry->qmPlayer->player->user->usernames()->where("ladder_id", '=', $ladderId)->get();

                foreach ($players as $player)
                {
                    if ($player->clanPlayer && $player->clanPlayer->clan->id == $opponentClanId)
                    {
                        $opponentClanName = \App\Clan::where('id', $opponentClanId)->first()->short;
                        $currentPlayerName = $qmEntry->qmPlayer->player->username;
                        $playerName = $player->username;
                        $currentClanName = $currentUserClanPlayer->clan->short;
                        Log::info("(CurrentUser=[$currentClanName]" . $currentUserClanPlayer->player->username . ") Player being matched: '$currentPlayerName' has another player: '$playerName' who belongs to opponent clan: '$opponentClanName'.");
                        $canMatch = false;
                        break;
                    }
                }

                if (!$canMatch)
                    break;
            }

            //loop through the members in opponent clan, check if any opponent is also in my clan
            foreach ($opponentQmEntries as $opponentQmEntry)
            {
                $opponentPlayers = $opponentQmEntry->qmPlayer->player->user->usernames()->where("ladder_id", '=', $ladderId)->get();

                foreach ($opponentPlayers as $opponentPlayer)
                {
                    if ($opponentPlayer->clanPlayer && $opponentPlayer->clanPlayer->clan->id == $currentUserClanPlayer->clan->id)
                    {
                        $myClanName = \App\Clan::where('id', $currentUserClanPlayer->clan->id)->first()->short;
                        $oppontnePlayerName = $opponentQmEntry->qmPlayer->player->username;
                        $playerName = $opponentPlayer->username;
                        $currentClanName = $currentUserClanPlayer->clan->short;
                        Log::info("(CurrentUser=[$currentClanName]" . $currentUserClanPlayer->player->username . ") Player being matched: '$oppontnePlayerName' has another player: '$playerName' who belongs to my clan: '$myClanName'.");
                        $canMatch = false;
                        break;
                    }
                }

                if (!$canMatch)
                    break;
            }

            if ($canMatch)
                $result[$clanId] = $opponentQmEntries;
        }

        return $result;
    }
}
