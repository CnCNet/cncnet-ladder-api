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
            Log::info("ClanMatchupHandler ** Clan: $currentUserClan has insufficient players ready for a match.");
            return;
        }

        Log::info("ClanMatchupHandler ** Clan $currentUserClan has players for a match.");

        $opponentsGroupedByClan = $this->groupPlayersByClanFromOpponentEntries($opponentQmQueueEntries, $currentUserClanPlayer);
        $validClanOpponentEntries = $this->createValidClanOpponentsCollection($opponentsGroupedByClan, $minClanPlayersRequiredToMatch);
        $validOpponentsCount = $validClanOpponentEntries->count();

        Log::info("ClanMatchupHandler ** Clan $currentUserClan has: $validOpponentsCount opponents available.");

        if ($validOpponentsCount > 0)
        {
            return $this->createMatch(
                $ladderMaps,
                $validClanOpponentEntries
            );
        }
    }

    /**
     * 
     * @param mixed $playersByClanMap 
     * @param mixed $minClanPlayersRequired 
     * @return Collection
     */
    private function createValidClanOpponentsCollection($playersByClanMap, $minClanPlayersRequired)
    {
        $validOpponentEntries = (new QmQueueEntry())->newCollection();

        foreach ($playersByClanMap as $clanId => $opponentQmQueueEntryArr)
        {
            if (count($opponentQmQueueEntryArr) < $minClanPlayersRequired)
            {
                Log::info("ClanMatchupHandler ** Clan $clanId not ready");
                continue;
            }

            # Got this far, the clan has enough players to be classed as opponents
            foreach ($opponentQmQueueEntryArr as $opponentQmQueueEntry)
            {
                $validOpponentEntries->add($opponentQmQueueEntry);
            }
        }

        return $validOpponentEntries;
    }


    /**
     * 
     * @param mixed $opponentQmQueueEntries 
     * @param mixed $currentUserClanPlayer 
     * @return array 
     */
    private function groupPlayersByClanFromOpponentEntries($opponentQmQueueEntries, $currentUserClanPlayer)
    {
        $playersByClanMap = [];
        foreach ($opponentQmQueueEntries as $opponentQmQueueEntry)
        {
            $opponentQmMatchPlayer = $opponentQmQueueEntry->qmPlayer;
            $opponentClanId = $opponentQmMatchPlayer->clan_id;

            if ($opponentClanId !== $currentUserClanPlayer->clan_id)
            {
                $playersByClanMap[$opponentQmMatchPlayer->clan_id][] = $opponentQmQueueEntry;
            }
        }
        return $playersByClanMap;
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

        Log::info("ClanMatchupHandler ** checkForValidClanMatchup: Clan players ready ($clanPlayerCountReady / $minCountRequired)");

        return ($clanPlayerCountReady >= $minCountRequired);
    }
}
