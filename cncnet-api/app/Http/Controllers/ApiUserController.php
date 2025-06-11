<?php

namespace App\Http\Controllers;

use App\Http\Services\PlayerService;
use App\Http\Services\UserService;
use App\Models\Ladder;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiUserController extends Controller
{
    private $playerService;
    private $userService;

    public function __construct()
    {
        $this->playerService = new PlayerService();
        $this->userService = new UserService();
    }

    public function getUserInfo(Request $request)
    {
        try
        {
            $user = $request->user();
            return $user;
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
            return Ladder::getAllowedQMLaddersByUser($user);
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
