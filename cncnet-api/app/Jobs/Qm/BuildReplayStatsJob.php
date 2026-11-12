<?php

namespace App\Jobs\Qm;

use App\Http\Services\ReplayService;
use App\Models\GameReplay;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reads a freshly uploaded replay: its canonical seals and chat always, and the game's statistics when the
 * game has no current ones yet. Runs on the post-game queue so the upload request never waits on it.
 */
class BuildReplayStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 120;

    private int $gameReplayId;

    public function __construct(int $gameReplayId)
    {
        $this->onQueue('saveladderresult');
        $this->gameReplayId = $gameReplayId;
    }

    public function handle(): void
    {
        $replay = GameReplay::find($this->gameReplayId);
        if ($replay === null)
        {
            return;
        }

        $service = new ReplayService();
        $service->storeSeals($replay);
        $service->storeChat($replay);

        if ($service->needsStats($replay->game_id))
        {
            $service->buildStats($replay);
        }
    }
}
