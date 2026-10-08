<?php

namespace App\Http\Controllers;

use App\Http\Services\ReplayService;
use App\Models\GameReplay;
use App\Models\Ladder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReplayController extends Controller
{
    private $replayService;

    public function __construct()
    {
        $this->replayService = new ReplayService();
    }

    /**
     * Serves a stored replay file.
     *
     * Replays live on a private disk and are only ever handed out through here, so access follows
     * the ladder's replay tier - see Ladder::allowedToDownloadReplays.
     */
    public function download(Request $request, $replayId)
    {
        $user = $request->user();

        $replay = GameReplay::find($replayId);
        if ($replay === null)
        {
            abort(404);
        }

        $ladder = self::ladderForReplay($replay);
        if ($ladder === null || !$ladder->allowedToDownloadReplays($user))
        {
            Log::warning("ReplayController: user {$user->id} was denied replay {$replayId}.");
            abort(403);
        }

        $path = $this->replayService->replayPath($replay);
        if ($path === null)
        {
            // Row exists but the file is gone - most likely evicted by the storage budget.
            Log::warning("ReplayController: replay {$replayId} has no file on disk.");
            abort(404);
        }

        return response()->download($path, $this->replayService->downloadName($replay));
    }

    /**
     * games has no ladder_id column - the ladder is reached via the monthly ladder_history row.
     */
    private static function ladderForReplay(GameReplay $replay): ?Ladder
    {
        $history = optional($replay->game)->ladderHistory;

        return $history === null ? null : Ladder::find($history->ladder_id);
    }
}
