<?php

namespace App\Extensions\Qm\Matchup;

use App\Http\Services\QuickMatchService;
use App\Models\LadderHistory;
use App\Models\QmMatchPlayer;
use App\Models\QmQueueEntry;
use Illuminate\Support\Facades\Log;

abstract class BaseMatchupHandler
{
    public $quickMatchService;
    public LadderHistory $history;
    public QmQueueEntry $qmQueueEntry;
    public QmMatchPlayer $qmPlayer;
    public int $gameType;
    public int $currentUserTier;
    public bool $matchHasObservers;

    public function __construct(QmQueueEntry $qmQueueEntry, int $gameType)
    {
        $this->quickMatchService = new QuickMatchService();
        $this->qmQueueEntry = $qmQueueEntry;
        $this->history = $this->qmQueueEntry->ladderHistory;
        $this->qmPlayer = $this->qmQueueEntry->qmPlayer;
        $this->gameType = $gameType;
        $this->currentUserTier = $this->qmPlayer->player->user->getUserLadderTier($this->history->ladder)->tier;
        $this->matchHasObservers = $this->qmPlayer->isObserver();
    }

    public function createMatch($maps, $otherQMQueueEntries)
    {
        $filteredMaps = array_filter($maps, function ($map)
        {
            return
                !strpos($map->description, 'Map Info')
                && !strpos($map->description, 'Map Guide')
                && !strpos($map->description, 'Ladder Guide')
                && !strpos($map->description, 'Ladder Rules');
        });

        $this->removeQueueEntry();

        return $this->quickMatchService->createQmMatch(
            $this->qmPlayer,
            $this->currentUserTier,
            $filteredMaps,
            $otherQMQueueEntries,
            $this->qmQueueEntry,
            $this->gameType
        );
    }

    public function removeQueueEntry()
    {
        Log::info("Removing queue entry for " . $this->qmPlayer->player->username);
        $this->qmQueueEntry->delete();
    }

    public abstract function matchup(): void;
}
