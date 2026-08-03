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
     * Replays are held on a private disk and only ever handed out through here, so access can be
     * restricted. During the beta that means staff only, controlled by the
     * 'replays.staff_only_downloads' config flag.
     */
    public function download(Request $request, $replayId)
    {
        $user = $request->user();

        $replay = GameReplay::find($replayId);
        if ($replay === null)
        {
            abort(404);
        }

        if (config('replays.staff_only_downloads') && !$this->userIsStaff($user, $replay))
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
     * Global staff, or a moderator/admin of the ladder the game belongs to.
     */
    private function userIsStaff($user, GameReplay $replay): bool
    {
        if ($user === null)
        {
            return false;
        }

        if ($user->isModerator())
        {
            return true;
        }

        $game = $replay->game;
        if ($game === null)
        {
            return false;
        }

        // games has no ladder_id column - the ladder is reached via the monthly ladder_history row.
        $history = $game->ladderHistory;
        if ($history === null)
        {
            return false;
        }

        $ladder = Ladder::find($history->ladder_id);
        if ($ladder === null)
        {
            return false;
        }

        return $user->isLadderMod($ladder) || $user->isLadderAdmin($ladder);
    }
}
