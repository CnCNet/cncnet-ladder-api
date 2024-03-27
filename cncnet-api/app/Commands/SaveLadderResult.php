<?php

namespace App\Commands;

use App\Commands\Command;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SaveLadderResult implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Dispatchable, Queueable;

    public $dmpFile;
    public $ladderId;
    public $gameId;
    public $playerId;
    public $pingSent;
    public $pingReceived;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($dmpFile, $ladderId, $gameId, $playerId, $pingSent, $pingReceived)
    {
        //
        $this->dmpFile = $dmpFile;
        $this->ladderId = $ladderId;
        $this->gameId = $gameId;
        $this->playerId = $playerId;
        $this->pingSent = $pingSent;
        $this->pingReceived = $pingReceived;
        $this->onQueue('saveladderresult');
    }
    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->delete();
        $alc = new \App\Http\Controllers\ApiLadderController;
        $alc->saveLadderResult($this->dmpFile, $this->ladderId, $this->gameId, $this->playerId, $this->pingSent, $this->pingReceived);
        return 0;
    }
}
