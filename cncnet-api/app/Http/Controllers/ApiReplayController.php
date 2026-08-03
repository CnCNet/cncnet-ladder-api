<?php

namespace App\Http\Controllers;

use App\Http\Services\ReplayService;
use App\Models\Game;
use App\Models\Ladder;
use App\Models\Player;
use App\Models\PlayerGameReport;
use App\Models\QmMatchPlayer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiReplayController extends Controller
{
    private $replayService;

    public function __construct()
    {
        $this->replayService = new ReplayService();
    }

    /**
     * Accepts a replay recorded by the Quick Match spawner for a finished game.
     *
     * A replay is only accepted when the authenticated user owns the player it is being uploaded
     * for, and that player actually took part in the game. Without both checks any authenticated
     * user could attach arbitrary files to any game.
     */
    public function uploadReplay(Request $request, $ladderId, $gameId, $playerId)
    {
        $user = $request->user();

        try
        {
            $request->validate([
                // max: is in kilobytes.
                'file' => 'required|file|max:' . ((int) config('replays.max_upload_mb') * 1024),
            ]);

            $game = Game::find($gameId);
            if ($game === null)
            {
                return response()->json(['message' => 'Game not found'], 404);
            }

            // games has no ladder_id column - despite one being listed in the model's $fillable.
            // The ladder is reached through the monthly ladder_history row instead.
            $history = $game->ladderHistory;
            if ($history === null || (int) $history->ladder_id !== (int) $ladderId)
            {
                return response()->json(['message' => 'Game does not belong to this ladder'], 404);
            }

            $ladder = Ladder::find($ladderId);
            $rules = $ladder ? $ladder->qmLadderRules : null;
            if ($rules !== null && !$rules->enable_replays)
            {
                // The ladder has replays switched off; treat a straggling upload as a no-op rather
                // than storing a file nobody asked for.
                return response()->json(['message' => 'Replays are not enabled for this ladder'], 403);
            }

            $player = Player::find($playerId);
            if ($player === null)
            {
                return response()->json(['message' => 'Player not found'], 404);
            }

            // The uploader must own this player account.
            if ((int) $player->user_id !== (int) $user->id)
            {
                Log::warning("ApiReplayController: user {$user->id} tried to upload a replay as player {$playerId}.");
                return response()->json(['message' => 'You may only upload replays for your own account'], 403);
            }

            // ... and must have actually played in this game.
            $participated = PlayerGameReport::where('game_id', $game->id)
                ->where('player_id', $player->id)
                ->exists();

            if (!$participated && $game->qm_match_id)
            {
                // player_game_reports is written by SaveLadderResultJob, which runs on a queue, so
                // it usually does not exist yet when a client uploads immediately after the match.
                // Whoever finishes first would otherwise always be rejected. qm_match_players is
                // written when the match is created, so it is available straight away and is just
                // as authoritative about who was actually in the game.
                $participated = QmMatchPlayer::where('qm_match_id', $game->qm_match_id)
                    ->where('player_id', $player->id)
                    ->exists();
            }

            if (!$participated)
            {
                Log::warning("ApiReplayController: player {$playerId} is not part of game {$gameId}.");
                return response()->json(['message' => 'Player did not participate in this game'], 403);
            }

            $replay = $this->replayService->storeReplay(
                $game->id,
                $player->id,
                $user->id,
                $request->file('file')
            );

            return response()->json([
                'message'   => 'Replay uploaded successfully',
                'game_id'   => $game->id,
                'player_id' => $player->id,
                'file_size' => $replay->file_size,
            ], 200);
        }
        catch (Exception $ex)
        {
            Log::error("ApiReplayController: error uploading replay: " . $ex->getMessage() . " : " . $ex->getTraceAsString());
        }

        return response()->json(['message' => 'Replay not uploaded'], 400);
    }
}
