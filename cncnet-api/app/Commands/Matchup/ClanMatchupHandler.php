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
                    Log::info("ClanMatchupHandler ** Player " . $qmQueueEntry->qmPlayer->player->username . " ready from Clan:" . $qmQueueEntry->qmPlayer->clan->short);

                    if ($qmQueueEntry->id == $this->qmQueueEntry->id)
                    {
                        # Don't add ourselves
                        continue;
                    }
                    $readyQMQueueEntries->add($qmQueueEntry);
                }
            }
        }

        $playersReadyCount = $readyQMQueueEntries->count() + 1; # Add ourselves to this count

        if ($playersReadyCount === $playerCountForMatchup)
        {
            return $this->createMatch(
                $ladderMaps,
                $readyQMQueueEntries
            );
        }
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
}
