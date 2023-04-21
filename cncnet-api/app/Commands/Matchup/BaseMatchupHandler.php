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
    public $currentUserPlayerTier;

    public function __construct($history, $qEntry, $qmPlayer, $gameType)
    {
        $this->quickMatchService = new QuickMatchService();
        $this->history = $history;
        $this->qmQueueEntry = $qEntry;
        $this->qmPlayer = $qmPlayer;
        $this->gameType = $gameType;
        $this->currentUserPlayerTier = $this->qmPlayer->player->getCachedPlayerTierByLadderHistory($this->history);
    }

    public function createMatch($maps, $opponents)
    {
        return $this->quickMatchService->createQmMatch(
            $this->qmPlayer,
            $this->currentUserPlayerTier,
            $maps,
            $opponents,
            $this->qmQueueEntry,
            $this->gameType
        );
    }

    public function removeQueueEntry()
    {
        Log::info("Removing queue entry for " . $this->qmPlayer);
        $this->qmQueueEntry->delete();
    }

    public function matchup()
    {
    }
}
