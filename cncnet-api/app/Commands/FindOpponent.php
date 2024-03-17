<?php

namespace App\Commands;

use App\Commands\Matchup\ClanMatchupHandler;
use App\Commands\Matchup\PlayerMatchupHandler;
use App\Models\QmQueueEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FindOpponent implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Dispatchable, Queueable;

    public $qmQueueEntryId = null;
    public $gameType = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($id, $gameType)
    {
        $this->qmQueueEntryId = $id;
        $this->gameType = $gameType;
        $this->onQueue('findmatch');
    }

    /*public function queue($queue, $arguments)
    {
        $queue->pushOn('findmatch', $arguments);
    }*/

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('FIND OPPONENT');

        $this->delete();

        $qmQueueEntry = QmQueueEntry::find($this->qmQueueEntryId);

        if ($qmQueueEntry === null)
        {
            Log::info('No qmqueue entry');
            return;
        }

        $qmQueueEntry->touch();

        $qmPlayer = $qmQueueEntry->qmPlayer;

        # A player could cancel out of queue before this function runs
        if ($qmPlayer === null)
        {
            Log::info('Cancelled out');
            $qmQueueEntry->delete();
            return;
        }

        # Skip if the player has already been matched up
        if ($qmPlayer->qm_match_id !== null)
        {
            Log::info("FindOpponent ** qmPlayer->qm_match_id is not null.");
            $qmQueueEntry->delete();
            return;
        }

        $history = $qmQueueEntry->ladderHistory;

        if ($history === null)
        {
            Log::info("FindOpponent ** history is null.");
            $qmQueueEntry->delete();
            return;
        }

        $ladder = $history->ladder;

        if ($ladder === null)
        {
            Log::info("FindOpponent ** ladder is null.");
            $qmQueueEntry->delete();
            return;
        }

        $player = $qmPlayer->player;

        if ($player === null)
        {
            Log::info("FindOpponent ** player is null.");
            $qmQueueEntry->delete();
            return;
        }

        # map_bitfield is an old and unused bit of code
        $qmPlayer->map_bitfield = 0xffffffff;
        $qmPlayer->save();

        if ($ladder->clans_allowed)
        {
            $matchupHandler = new ClanMatchupHandler(
                $history,
                $qmQueueEntry,
                $qmPlayer,
                $this->gameType
            );
        }
        else
        {
            $matchupHandler = new PlayerMatchupHandler(
                $history,
                $qmQueueEntry,
                $qmPlayer,
                $this->gameType
            );
        }

        return $matchupHandler->matchup();
    }
}
