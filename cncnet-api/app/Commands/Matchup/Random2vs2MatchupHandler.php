<?php

namespace App\Commands\Matchup;

use App\QmQueueEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class Random2vs2MatchupHandler extends BaseMatchupHandler
{
    public function matchup()
    {
        Log::info("Random2vs2MatchupHandler ** Started");

        $ladder = $this->history->ladder;
        $ladderRules = $ladder->qmLadderRules;
        $ladderMaps = $ladder->mapPool->maps;

        $currentPlayer = $this->qmPlayer->player;
        $playerCountPerClanRequired = floor($ladderRules->player_count / 2); # (2) for a 2v2
        $playerCountForMatchup = $ladderRules->player_count; # (4) for a 2v2

        # Fetch all entries who are currently in queue for this ladder
        $allQMQueueEntries = QmQueueEntry::where('ladder_history_id', '=', $this->history->id)->get();

        $this->matchHasObservers = $this->checkMatchForObserver($allQMQueueEntries);
        if ($this->matchHasObservers === true)
        {
            $playerCountForMatchup = $playerCountForMatchup + 1; # E.g. 4 players + 1x observer
        }

        Log::info("Random2vs2MatchupHandler ** Players Per Clan Required: " . $playerCountPerClanRequired);
        Log::info("Random2vs2MatchupHandler ** Players For Matchup Required: " . $playerCountForMatchup);
        Log::info("Random2vs2MatchupHandler ** Match Has Observer Present: " . $this->matchHasObservers);


        # Group queue entries by TEAM Name assigned to the players at this point
        $groupedQmQueueEntriesByClan = $this->groupAndLimitRandomTeams($allQMQueueEntries, $playerCountPerClanRequired);

        # Collection of other QM Queued Players ready 
        $otherQMQueueEntries = (new QmQueueEntry())->newCollection();

        foreach ($groupedQmQueueEntriesByClan as $teamName => $allQMQueueEntries)
        {
            # Check this team has enough players to play
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
                    $otherQMQueueEntries->add($qmQueueEntry);
                }
            }

            if ($otherQMQueueEntries->count() == $playerCountForMatchup) //required number of players found
                break;
        }


        # Check for observers and add to our ready qm entries
        foreach ($allQMQueueEntries as $qmQueueEntry)
        {
            if (
                $qmQueueEntry->qmPlayer->isObserver() === true
                && $this->qmPlayer->id !== $qmQueueEntry->qmPlayer->id
            )
            {
                Log::info("Random2vs2MatchupHandler ** Adding observer to our ready entries: " . $qmQueueEntry->qmPlayer->player->username);
                $otherQMQueueEntries->add($qmQueueEntry);
                break; //1x only
            }
        }

        $playersReadyCount = $otherQMQueueEntries->count() + 1; # Add ourselves to this count
        Log::info("Random2vs2MatchupHandler ** Match has observer: " . $this->matchHasObservers);
        Log::info("Random2vs2MatchupHandler ** Player count for matchup: Ready: " . $playersReadyCount . "  Required: " . $playerCountForMatchup);

        if ($playersReadyCount === $playerCountForMatchup)
        {
            $commonQmMaps = $this->removeRejectedMaps($ladderMaps, $this->qmPlayer, $otherQMQueueEntries);

            if (count($commonQmMaps) <= 0)
            {
                Log::info("0 commonQmMaps found, exiting...");
            }
            else
            {
                $playerNames = implode(",", $this->getPlayerNamesInQueue($otherQMQueueEntries));
                Log::info("Launching Random2vs2Matchup with players $playerNames, " . $currentPlayer->username);

                return $this->createMatch(
                    $commonQmMaps,
                    $otherQMQueueEntries
                );
            }
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
            Log::info("Random2vs2MatchupHandler ** Checking for observer: " . $qmQueueEntry->qmPlayer->player->username . " : " . $qmQueueEntry->qmPlayer->isObserver());

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

            if ($qmEntry->qmPlayer->team_name == $currentQmPlayer->team_name)
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
                Log::info("Random2vs2MatchupHandler.removeRejectedMaps() ** Rejecting QmMap: " . $qmMap->map->name);
            }
        }

        return $commonQMMaps;
    }

    /**
     * Return QM Queue Entries grouped by "Team1" and "Team2"
     * @param mixed $allQMQueueEntries - All queue entries
     * @param mixed $limit - Number of entries per team
     * @return array 
     */
    private function groupAndLimitRandomTeams($allQMQueueEntries, $limit)
    {
        $result = [];
        $teamsIndex = 0;
        $teamNames = [
            "Team1",
            "Team2"
        ];

        # Loop over all QM Queue Entries and group them by clan
        foreach ($allQMQueueEntries as $qmQueueEntry)
        {
            # Don't add observers to counts
            if ($qmQueueEntry->qmPlayer->isObserver())
            {
                continue;
            }

            $teamName = $teamNames[$teamsIndex];

            if (isset($result[$teamName]))
            {
                $count = count($result[$teamName]);

                // Assign the team name to this player. Fake clan basically.
                $qmQueueEntry->team_name = $teamName;
                $qmQueueEntry->save();

                # We've reached enough players in this clan for a match
                if ($count == $limit)
                {
                    $teamsIndex++;
                    continue;
                }
            }
            $result[$teamName][] = $qmQueueEntry;
        }

        return $result;
    }
}
