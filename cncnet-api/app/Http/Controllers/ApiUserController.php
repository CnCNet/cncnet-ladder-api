<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\PlayerService;
use App\Http\Services\UserService;
use App\Ladder;
use Exception;
use Illuminate\Http\JsonResponse;

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
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }

    public function getPrivateLadders(Request $request)
    {
        try
        {
            $user = $request->user();
            return Ladder::getAllowedQMLaddersByUser($user);
        }
        catch (Exception $ex)
        {
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
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }
}
