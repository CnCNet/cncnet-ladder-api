<?php

namespace App\Http\Controllers;

use App\Http\Services\LadderService;
use App\Http\Services\PlayerService;
use App\Http\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiUserController extends Controller
{
    private $playerService;
    private $userService;
    private $ladderService;

    public function __construct()
    {
        $this->playerService = new PlayerService();
        $this->userService = new UserService();
        $this->ladderService = new LadderService();
    }

    public function getUserInfo(Request $request)
    {
        try
        {
            $user = $request->user();

            $elo = $user->userRatings()->get([
                'ladder_id',
                'rating',
                'deviation',
                'elo_rank',
                'alltime_rank',
                'rated_games',
                'active',
            ]);

            $userData = $user->toArray();
            $userData['elo'] = $elo;

            return $userData;
        }
        catch (Exception $ex)
        {
            Log::error($ex);
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }

    /**
     * Return users active player usernames
     * @param Request $request 
     * @return array|JsonResponse 
     */
    public function getAccount(Request $request)
    {
        try
        {
            $user = $request->user();
            return $this->playerService->getActivePlayersByUser($user);
        }
        catch (Exception $ex)
        {
            Log::error($ex);
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }

    public function getPrivateLadders(Request $request)
    {
        try
        {
            $user = auth('api')->user();
            return $this->ladderService->getAllowedPrivateLadders($user);
        }
        catch (Exception $ex)
        {
            Log::error($ex);
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }

    public function getUserPreferences(Request $request)
    {
        try
        {
            $user = $request->user();
            return $this->userService->getUserPreferences($user);
        }
        catch (Exception $ex)
        {
            Log::error($ex);
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }

    public function updateUserPreferences(Request $request)
    {
        try
        {
            $user = $request->user();

            return $this->userService->updateUserPreferencesFromRequest(
                $request,
                $user
            );
        }
        catch (Exception $ex)
        {
            Log::error($ex);
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }
}
