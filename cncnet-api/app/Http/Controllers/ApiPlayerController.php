<?php

namespace App\Http\Controllers;

use App\Http\Services\AuthService;
use App\Http\Services\PlayerService;
use App\Models\PlayerActiveHandle;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiPlayerController extends Controller
{
    private $authService;
    private $playerService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->playerService = new PlayerService();
    }

    /**
     * 
     * @param Request $request 
     * @return JsonResponse|void 
     */
    public function getUsernames(Request $request)
    {
        try
        {
            $this->validate($request, [
                'ladderAbbrev' => 'required|string'
            ]);

            # Check we're auth'd
            $user = $this->authService->getUser($request)["user"];
            if ($user == null)
            {
                return response()->json(["message" => "Not authorized"], 403);
            }

            # Check ladder exists
            $ladder = \App\Models\Ladder::where("abbreviation", '=', $request->ladderAbbrev)->first();
            if ($ladder === null)
            {
                return response()->json(["message" => "Ladder does not exist"], 400);
            }

            $response = [];
            $players = $user->usernames()->where("ladder_id", $ladder->id)->get();

            $now = Carbon::now();
            $dateStart = $now->startOfMonth()->toDateTimeString();
            $dateEnd = $now->endOfMonth()->toDateTimeString();
            $activeHandles = \App\Models\PlayerActiveHandle::getUserActiveHandles($user->id, $dateStart, $dateEnd)
                ->where('ladder_id', $ladder->id)
                ->get();

            foreach ($players as $player)
            {
                $player["active"] = $activeHandles->where('player_id', $player->id)->count() > 0;
                $response[] = $player;
            }

            return response()->json($response, 200);
        }
        catch (ValidationException $ex)
        {
            return response()->json(["message" => $ex->getMessage()], 400);
        }
        catch (Exception $ex)
        {
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }

    /**
     * 
     * @param Request $request 
     * @return JsonResponse|void 
     */
    public function createPlayer(Request $request)
    {
        try
        {
            $this->validate($request, [
                'username' => 'required|string|regex:/^[a-zA-Z0-9_\[\]\{\}\^\`\-\\x7c]+$/|max:11', //\x7c = | aka pipe,
                'ladderAbbrev' => 'required|string'
            ]);

            # Check we're auth'd
            $user = $this->authService->getUser($request)["user"];
            if ($user == null)
            {
                return response()->json(["message" => "Not authorized"], 403);
            }

            # Check ladder exists
            $ladder = \App\Models\Ladder::where("abbreviation", '=', $request->ladderAbbrev)->first();
            if ($ladder === null)
            {
                return response()->json(["message" => "Ladder does not exist"], 400);
            }

            $player = $this->playerService->addPlayerToUserAccount($request->username, $user, $ladder->id);
            if ($player == null)
            {
                return response()->json(["message" => "Username has been taken"], 400);
            }

            return response()->json(["message" => "Successfully created"], 200);
        }
        catch (ValidationException $ex)
        {
            return response()->json(["message" => $ex->getMessage()], 400);
        }
        catch (Exception $ex)
        {
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }

    /**
     * 
     * @param Request $request 
     * @return JsonResponse|void 
     */
    public function togglePlayerStatus(Request $request)
    {
        try
        {
            $this->validate($request, [
                'username' => 'required|string', //\x7c = | aka pipe,
                'ladderAbbrev' => 'required|string'
            ]);

            # Check we're auth'd
            $user = $this->authService->getUser($request)["user"];
            if ($user == null)
            {
                return response()->json(["message" => "Not authorized"], 403);
            }

            # Check ladder exists
            $ladder = \App\Models\Ladder::where("abbreviation", '=', $request->ladderAbbrev)->first();
            if ($ladder === null)
            {
                return response()->json(["message" => "Ladder does not exist"], 400);
            }

            $maxActivePlayersAllowed = $ladder->qmLadderRules->max_active_players;

            // Check request is linked to the auth'd user
            $player = \App\Models\Player::where("username", "=", $request->username)
                ->where("ladder_id", "=", $ladder->id)
                ->where("user_id", "=", $user->id)
                ->first();

            if ($player == null)
            {
                return response()->json(["message" => "You do not own this username"], 400);
            }

            $date = Carbon::now();
            $startOfMonth = $date->startOfMonth()->toDateTimeString();
            $endOfMonth = $date->endOfMonth()->toDateTimeString();

            // Get the player thats being requested to change
            $activeHandle = PlayerActiveHandle::getPlayerActiveHandle($player->id, $ladder->id, $startOfMonth, $endOfMonth);

            // Get count of how many games this user has played
            $hasActiveHandlesGamesPlayed = PlayerActiveHandle::getUserActiveHandleGamesPlayedCount($activeHandle, $startOfMonth, $endOfMonth);

            // Allowed to remove the active handle if no games have been played yet
            if ($activeHandle != null && $hasActiveHandlesGamesPlayed < 1)
            {
                $activeHandle->delete();

                return response()->json(["message" => "Username deactivated"], 200);
            }
            else if ($activeHandle != null && $hasActiveHandlesGamesPlayed > 0)
            {
                $response = "Unable to deactivate username: $player->username because $player->username has played games this month already.";
                return response()->json(["message" => $response], 400);
            }
            else
            {
                // Check if there are active handles within this month
                $activeHandles = PlayerActiveHandle::getUserActiveHandles($user->id, $startOfMonth, $endOfMonth)->where('ladder_id', $ladder->id)->get();

                //filter out any whose ladder_id does not match
                $activeHandles = $activeHandles->filter(function ($tempHandle) use (&$ladder)
                {
                    return $tempHandle->ladder_id == $ladder->id && $tempHandle->player->ladder_id == $ladder->id;
                });

                if ($activeHandles->count() >= $maxActivePlayersAllowed)
                {
                    $response = "You already have an active username, deactivate that username to activate another. The maximum amount of active usernames at one time is $maxActivePlayersAllowed";
                    return response()->json(["message" => $response], 400);
                }

                $activeHandle = PlayerActiveHandle::setPlayerActiveHandle($ladder->id, $player->id, $user->id);
                $response = $player->username . ' is now active on the ladder. You can now play in Ranked Matches!';
                return response()->json(["message" => $response], 200);
            }
        }
        catch (ValidationException $ex)
        {
            return response()->json(["message" => $ex->getMessage()], 400);
        }
        catch (Exception $ex)
        {
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }
}
