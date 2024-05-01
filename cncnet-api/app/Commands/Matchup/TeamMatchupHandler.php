<?php

namespace App\Commands\Matchup;

use App\Models\QmQueueEntry;

/**
 * @deprecated
 */
class TeamMatchupHandler extends BaseMatchupHandler
{

    public function matchup()
    {
        $ladder = $this->history->ladder;
        $ladderRules = $ladder->qmLadderRules;
        $ladderMaps = $ladder->mapPool->maps;

        $numberOfTeamRequired = 2;
        $numberOfPlayerRequired = $ladderRules->player_count;

        $currentPlayer = $this->qmPlayer->player;

        # Fetch all entries who are currently in queue for this ladder
        $allQMQueueEntries = QmQueueEntry::query()
            ->where('qm_match_player_id', '<>', $this->qmQueueEntry->qmPlayer->id)
            ->where('ladder_history_id', '=', $this->history->id)
                ->get();

        // get all observers from qm queue entries
        $observersQmQueueEntries = $allQMQueueEntries->filter(function($qmQueueEntry) {
            return $qmQueueEntry->qmPlayer->isObserver();
        });
        $this->matchHasObservers = $observersQmQueueEntries->count() > 0;

        // todo : match up x players by their ranking

    }
}