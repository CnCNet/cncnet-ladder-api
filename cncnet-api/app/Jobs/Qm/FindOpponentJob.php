<?php

namespace App\Jobs\Qm;

use App\Extensions\Qm\Matchup\ClanMatchupHandler;
use App\Extensions\Qm\Matchup\PlayerMatchupHandler;
use App\Extensions\Qm\Matchup\TeamMatchupHandler;
use App\Models\QmQueueEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FindOpponentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $qmQueueEntry;
    private int $gameType;

    /**
     * Create a new job instance.
     */
    public function __construct($qmQueueEntry, int $gameType)
    {
        $this->onQueue('findmatch');

        $this->qmQueueEntry = $qmQueueEntry;
        $this->gameType = $gameType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->qmQueueEntry = QmQueueEntry::find($this->qmQueueEntry);

        if(!$this->readyToFindOpponent()) {
            Log::info('Not ready to find opponents');
            return;
        }

        $this->qmQueueEntry->touch();

        // map_bitfield is an old and unused bit of code
        $this->qmQueueEntry->qmPlayer->map_bitfield = 0xffffffff;
        $this->qmQueueEntry->qmPlayer->save();

        if($this->qmQueueEntry->ladderHistory->ladder->clans_allowed) {
            $matchupHandler = new ClanMatchupHandler($this->qmQueueEntry, $this->gameType);
        }
        elseif ($this->qmQueueEntry->ladderHistory->ladder->qmLadderRules->player_count > 2) {
            $matchupHandler = new TeamMatchupHandler($this->qmQueueEntry, $this->gameType);
        }
        else {
            $matchupHandler = new PlayerMatchupHandler($this->qmQueueEntry, $this->gameType);
        }

        Log::debug('[FindOpponentJob] : matchup handler : ' . get_class($matchupHandler));
        $matchupHandler->matchup();
    }

    private function readyToFindOpponent() {

        if (!isset($this->qmQueueEntry)) {
            Log::info('No qmqueue entry');
            return false;
        }

        // A player could cancel out of queue before this function runs
        if ($this->qmQueueEntry->qmPlayer === null) {
            Log::info('Cancelled out');
            $this->qmQueueEntry->delete();
            return false;
        }

        // Skip if the player has already been matched up
        if ($this->qmQueueEntry->qmPlayer->qm_match_id !== null) {
            Log::info("FindOpponent ** qmPlayer->qm_match_id is not null.");
            $this->qmQueueEntry->delete();
            return false;
        }

        if ($this->qmQueueEntry->ladderHistory === null)
        {
            Log::info("FindOpponent ** history is null.");
            $this->qmQueueEntry->delete();
            return false;
        }

        if ($this->qmQueueEntry->ladderHistory->ladder === null)
        {
            Log::info("FindOpponent ** ladder is null.");
            $this->qmQueueEntry->delete();
            return false;
        }

        if ($this->qmQueueEntry->qmPlayer->player === null)
        {
            Log::info("FindOpponent ** player is null.");
            $this->qmQueueEntry->delete();
            return false;
        }

        return true;
    }
}
