<?php

namespace App\Commands\Matchup;

use App\QmQueueEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ClanMatchupHandler extends BaseMatchupHandler
{
    public function matchup()
    {
        $ladder = $this->history->ladder;
        $ladderRules = $ladder->qmLadderRules;
        $ladderMaps = $ladder->mapPool->maps;

        $currentPlayer = $this->qmPlayer->player;
        $currentUserClanPlayer = $currentPlayer->clanPlayer;
        $playerCountPerClanRequired = floor($ladderRules->player_count / 2); # (2) for a 2v2
        $playerCountForMatchup = $ladderRules->player_count; # (4) for a 2v2

        if ($currentUserClanPlayer == null)
        {
            Log::info("ClanMatchupHandler ** Clan Player Null, removing $currentPlayer from queue");
            return $this->removeQueueEntry();
        }

        # Fetch all entries who are currently in queue for this ladder
        $allQMQueueEntries = QmQueueEntry::where('ladder_history_id', '=', $this->history->id)->get();

        # Group queue entries by clan and make sure we only have the exact number of players in each
        $groupedQmQueueEntriesByClan = $this->groupAndLimitClanPlayers($allQMQueueEntries, $playerCountPerClanRequired);

        # remove other clans who current user has a player who belongs to it
        // $groupedQmQueueEntriesByClan = $this->removeClansCurrentPlayerIsIn($currentUserClanPlayer, $ladder->id, $groupedQmQueueEntriesByClan);

        # Collection of QM Queued Players ready 
        $readyQMQueueEntries = (new QmQueueEntry())->newCollection();

        foreach ($groupedQmQueueEntriesByClan as $clanId => $allQMQueueEntries)
        {
            # Check this clan has enough players to play
            # Add them to players ready if so
            if (count($allQMQueueEntries) == $playerCountPerClanRequired)
            {
                foreach ($allQMQueueEntries as $qmQueueEntry)
                {
                    if ($qmQueueEntry->id == $this->qmQueueEntry->id)
                    {
                        # Don't add ourselves
                        continue;
                    }
                    $readyQMQueueEntries->add($qmQueueEntry);
                }
            }

            if ($readyQMQueueEntries->count() == $playerCountForMatchup) //required number of players found
                break;
        }

        $playersReadyCount = $readyQMQueueEntries->count() + 1; # Add ourselves to this count

        if ($playersReadyCount === $playerCountForMatchup)
        {
            $commonQmMaps = $this->removeRejectedMaps($ladderMaps, $this->qmPlayer, $readyQMQueueEntries);

            if (count($commonQmMaps) <= 0)
            {
                Log::info("0 commonQmMaps found, exiting...");
            }
            else
            {
                $playerNames = implode(",", $this->getPlayerNamesInQueue($readyQMQueueEntries));
                Log::info("Launching clan match with players $playerNames, " . $currentPlayer->username);
                return $this->createMatch(
                    $commonQmMaps,
                    $readyQMQueueEntries
                );
            }
        }
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
