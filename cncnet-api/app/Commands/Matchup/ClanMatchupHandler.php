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

        if ($playerCount == $minPlayerCountForLadder)
        {
            return $this->createMatch(
                $ladderMaps,
                $players
            );
        }
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
