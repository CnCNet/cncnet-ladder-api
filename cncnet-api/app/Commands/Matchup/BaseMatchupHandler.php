<?php

namespace App\Commands\Matchup;

use App\Http\Services\QuickMatchService;
use Illuminate\Support\Facades\Log;

class BaseMatchupHandler
{
    public $quickMatchService;
    public $history;
    public $qmQueueEntry;
    public $qmPlayer;
    public $gameType;
    public $currentUserTier;
    public $matchHasObservers;

    public function __construct($history, $qEntry, $qmPlayer, $gameType)
    {
        $this->quickMatchService = new QuickMatchService();
        $this->history = $history;
        $this->qmQueueEntry = $qEntry;
        $this->qmPlayer = $qmPlayer;
        $this->gameType = $gameType;
        $this->currentUserTier = $this->qmPlayer->player->user->getUserLadderTier($history->ladder)->tier;
        $this->matchHasObservers = $qmPlayer->isObserver();
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

    public function matchup()
    {
    }
}
