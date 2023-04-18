<?php

namespace App\Commands\Matchup;

use App\QmQueueEntry;
use Illuminate\Support\Facades\Log;

class ClanMatchupHandler extends MatchupInterface
{
    public function matchup()
    {
        $ladder = $this->history->ladder;
        $ladderRules = $ladder->qmLadderRules;

        $currentUser = $this->qmPlayer->player->user;
        $currentUserSettings = $currentUser->userSettings;
        $currentPlayer = $this->qmPlayer->player;
        $currentPlayerRank = $currentPlayer->rank($this->history);
        $currentUserPlayerTier = $currentPlayer->getCachedPlayerTierByLadderHistory($this->history);
        $currentUserClanPlayer = $currentPlayer->clanPlayer;

        if ($currentUserClanPlayer == null)
        {
            Log::info("ClanMatchupHandler ** Clan Player Null, removing from queue");
            return $this->removeQueueEntry();
        }

        # Fetch all opponents who are currently in queue for this ladder
        $opponentQmQueueEntries = QmQueueEntry::where('qm_match_player_id', '<>', $this->qmQueueEntry->qmPlayer->id)
            ->where('ladder_history_id', '=', $this->history->id)
            ->get();


        # Check we have enough players in our clan before we go any further
        $minRequiredClanPlayerCount = floor($ladder->qmLadderRules->player_count / 2);

        $hasValidClanMatchup = $this->checkForValidClanMatchup(
            $opponentQmQueueEntries,
            $currentUserClanPlayer,
            $minRequiredClanPlayerCount
        );

        $currentUserClanName = $currentUserClanPlayer->clan->short;
        if (!$hasValidClanMatchup)
        {
            Log::info("ClanMatchupHandler ** Clan: $currentUserClanName has insufficient players ready for a match.");
            return;
        }

        Log::info("ClanMatchupHandler ** Clan $currentUserClanName has players for a match.");

        $clanLadderMaps = $ladder->mapPool->maps;
        $opponentCount = $opponentQmQueueEntries->count();

        Log::info("ClanMatchupHandler ** Opponent Count: $opponentCount");

        if ($opponentCount->count() >= $ladderRules->player_count - 1) // -1 = Minus ourselves
        {
            return $this->createMatch($currentUserPlayerTier, $clanLadderMaps, $opponentQmQueueEntries);
        }
    }


    /**
     * 
     * @param mixed $opponentQmQueueEntries - Everyone else but us in the queue
     * @param mixed $userClanPlayer - Current user in the queue requesting right now
     * @param mixed $minRequiredClanPlayerCount - Required number of players for a clan
     * @return bool 
     */
    private function checkForValidClanMatchup($opponentQmQueueEntries, $userClanPlayer, $minRequiredClanPlayerCount)
    {
        Log::info("FindOpponent ** checkForValidClanMatchup: Required clan players $minRequiredClanPlayerCount");

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

        Log::info("FindOpponent ** checkForValidClanMatchup: Counted clan players $clanPlayerCountReady");

        return ($clanPlayerCountReady == $minRequiredClanPlayerCount);
    }
}
