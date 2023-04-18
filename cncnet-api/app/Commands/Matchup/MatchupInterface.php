<?php

namespace App\Commands\Matchup;

use App\Http\Services\QuickMatchService;
use Illuminate\Support\Facades\Log;

class MatchupInterface
{
    public $quickMatchService;
    public $history;
    public $qmQueueEntry;
    public $qmPlayer;
    public $gameType;

    public function __construct($history, $qEntry, $qmPlayer, $gameType)
    {
        $this->quickMatchService = new QuickMatchService();
        $this->history = $history;
        $this->qmQueueEntry = $qEntry;
        $this->qmPlayer = $qmPlayer;
        $this->gameType = $gameType;
    }

    public function matchup()
    {
        Log::info("Matchup ** " . $this->qmQueueEntry);
    }

    public function createMatch($currentUserPlayerTier, $maps, $opponents)
    {
        return $this->quickMatchService->createQmMatch(
            $this->qmPlayer,
            $currentUserPlayerTier,
            $maps,
            $opponents,
            $this->qmQueueEntry,
            $this->gameType
        );
    }

    public function removeQueueEntry()
    {
        $this->qmQueueEntry->delete();
    }
}
