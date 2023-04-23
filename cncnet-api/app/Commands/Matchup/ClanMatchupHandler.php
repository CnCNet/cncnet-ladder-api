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
        $minClanPlayersRequiredToMatch = floor($ladder->qmLadderRules->player_count / 2);
        $minPlayerCountForLadder = $ladderRules->player_count;

        if ($currentUserClanPlayer == null)
        {
            Log::info("ClanMatchupHandler ** Clan Player Null, removing $currentPlayer from queue");
            return $this->removeQueueEntry();
        }

        $currentUserClan = $currentUserClanPlayer->clan;

        # Fetch all opponents who are currently in queue for this ladder
        $opponentQmQueueEntries = QmQueueEntry::where('qm_match_player_id', '<>', $this->qmQueueEntry->qmPlayer->id)
            ->where('ladder_history_id', '=', $this->history->id)
            ->get();

        # Check we have enough players in our clan before we go any further
        $currentClanHasRequiredPlayers = $this->checkCurrentClanHasRequiredPlayers(
            $opponentQmQueueEntries,
            $currentUserClanPlayer,
            $minClanPlayersRequiredToMatch
        );

        if (!$currentClanHasRequiredPlayers)
        {
            Log::info("ClanMatchupHandler ** Clan: " . $currentUserClan->short . " has insufficient players");
            return;
        }

        $clans = [];
        foreach ($opponentQmQueueEntries as $opponentQmQueueEntry)
        {
            $clans[$opponentQmQueueEntry->qmPlayer->clan_id][] = $opponentQmQueueEntry;
        }

        $players = (new QmQueueEntry())->newCollection();
        foreach ($clans as $clanId => $clanQueueEntries)
        {
            if (
                $currentUserClan->id === $clanId
                || count($clanQueueEntries) >= $minClanPlayersRequiredToMatch
            )
            {
                foreach ($clanQueueEntries as $clanQueueEntry)
                {
                    $players->add($clanQueueEntry);
                }
            }
        }

        $playerCount = $players->count() + 1; // Plus ourselves

        foreach ($players as $player)
        {
            Log::info("-- ClanMatchupHandler ** Player: " . $player->qmPlayer->player->username . " waiting");
        }

        Log::info("ClanMatchupHandler ** Clan: " . $currentUserClan->short . " has: $playerCount opponents available.");
        Log::info("ClanMatchupHandler ** Opponent Count: $playerCount // MinPlayerCountRequired: $minPlayerCountForLadder");

        //remove rejected maps
        $commonQmMaps = $this->removeRejectedMaps($ladderMaps, $this->qmPlayer, $players);

        if ($playerCount == $minPlayerCountForLadder)
        {
            return $this->createMatch(
                $commonQmMaps,
                $players
            );
        }
    }

    private function removeRejectedMaps($qmMaps, $currentQmPlayer, $qmEntries)
    {
        $team1[] = $currentQmPlayer;
        $team2[] = [];

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
            foreach ($allTeams as $team) # Loop through each team, if every member in team has rejected the map then exclude it
            {
                $match = true;
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

                if ($match)
                {
                    $commonQMMaps[] = $qmMap;
                }
                else
                {
                    Log::info("ClanMatchupHandler.removeRejectedMaps() ** Rejecting QmMap: " . $qmMap->map->name);
                }
            }
        }

        return $commonQMMaps;
    }

    /**
     * 
     * @param mixed $opponentQmQueueEntries - Everyone else but us in the queue
     * @param mixed $userClanPlayer - Current user in the queue requesting right now
     * @param mixed $minCountRequired - Required number of players for a clan
     * @return bool 
     */
    private function checkCurrentClanHasRequiredPlayers($opponentQmQueueEntries, $userClanPlayer, $minCountRequired)
    {
        # How many clan players do we have in the queue
        # Include ourselves = 1
        $clanPlayerCountReady = 1;

        # Everyone else in the queue thats not us
        foreach ($opponentQmQueueEntries as $opponentQmQueueEntry)
        {
            # Check we're in the same clan and increase our players in the clan
            $opponentQmMatchPlayer = $opponentQmQueueEntry->qmPlayer;

            if ($opponentQmMatchPlayer && $opponentQmMatchPlayer->clan_id == $userClanPlayer->clan_id)
            {
                $clanPlayerCountReady++;
            }
        }

        Log::info("ClanMatchupHandler ** " . $userClanPlayer->clan->short . " has $clanPlayerCountReady players ready");

        return ($clanPlayerCountReady >= $minCountRequired);
    }
}
