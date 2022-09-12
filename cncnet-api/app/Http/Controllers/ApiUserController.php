<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\AuthService;
use \App\Http\Services\PlayerService;
use \App\Http\Services\LadderService;
use \App\PlayerActiveHandle;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Log;
use Carbon\Carbon;

class ApiUserController extends Controller
{
    private $authService;
    private $ladderService;
    private $playerService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->playerService = new PlayerService;
        $this->ladderService = new LadderService;
    }

    public function getAccount(Request $request)
    {
        $user = $request->user();

        foreach (\App\Ladder::all() as $ladder)
        {
            $players = $user->usernames()->where('ladder_id', '=', $ladder->id)->get();

            if ($players->count() < 1)
            {
                // Auto-register a player for each ladder if there isn't already a player registered for this user
                $playerCreated = false;
                $oLadders = \App\Ladder::where('abbreviation', '=', $ladder->abbreviation)
                    ->where('id', '<>', $ladder->id)
                    ->get();
                foreach ($oLadders as $other)
                {
                    $oPlayers = $other->players()->where('user_id', '=', $user->id)->get();
                    foreach ($oPlayers as $op)
                    {
                        $this->playerService->addPlayerToUser($op->username, $user, $ladder->id);
                        $playerCreated = true;
                    }
                }
                if (!$playerCreated)
                {
                    $this->playerService->addPlayerToUser($user->name, $user, $ladder->id);
                }
            }
        }
        return $this->getActivePlayerList($user);
    }

    private function getActivePlayerList($user)
    {
        $date = Carbon::now();
        $startOfMonth = $date->startOfMonth()->toDateTimeString();
        $endOfMonth = $date->endOfMonth()->toDateTimeString();

        $activeHandles = PlayerActiveHandle::getUserActiveHandles($user->id, $startOfMonth, $endOfMonth)->get();

        $players = [];
        foreach ($activeHandles as $activeHandle)
        {
            if ($activeHandle->player->ladder->private == false)
                $players[] = $activeHandle->player;
        }

        // If they haven't selected a nickname yet
        // Get their last created

        if (count($players) == 0)
        {
            return $this->getTempNicks($user->id);
        }

        return $players;
    }

    /**
     * Returns a SINGLE nickname for all 3 ladder types
     */
    private function getTempNicks($userId)
    {
        $tempNicks = [];
        foreach (\App\Ladder::all() as $ladder)
        {
            $tempNick = $this->getTempNickByLadderType($ladder->id, $userId);
            if ($tempNick != null)
            {
                $tempNicks[] = $tempNick;
            }
        }
        return $tempNicks;
    }

    /**
     * Limit nicknames to the expired date, one per ladder type
     */
    private function getTempNickByLadderType($ladderId, $userId)
    {
        // Get this months ladder
        $date = Carbon::now();
        $start = $date->startOfMonth()->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        $ladderHistory = \App\LadderHistory::where("starts", "=", $start)
            ->where("ends", "=", $end)
            ->where('ladder_id', '=', $ladderId)
            ->first();

        // Detect if the player is active in the this months ladder already
        $tempNick = \App\PlayerHistory::join('players as p', 'p.id', '=', 'player_histories.player_id')
            ->where("player_histories.ladder_history_id", "=", $ladderHistory->id)
            ->where("user_id", $userId)
            ->where('ladder_id', $ladderId)
            ->select(["p.id", "p.username", "p.ladder_id", "p.card_id"])
            ->orderBy("id", "desc")
            ->first();

        // If they are, set their nick as the active handle
        if ($tempNick != null)
        {
            PlayerActiveHandle::setPlayerActiveHandle($ladderId, $tempNick->id, $userId);
            return $tempNick;
        }

        // Get nick last created limited by this new 1 nick rule
        $tempNick = \App\Player::where("user_id", $userId)
            ->where('ladder_id', $ladderId)
            ->orderBy("id", "desc")
            ->first();

        return $tempNick;
    }

    public function createUser(Request $request)
    {
        $token = JWTAuth::getToken();

        if ($request->email == null && $request->password == null)
        {
            return response()->json(['bad_parameters'], 400);
        }

        if ($token == null)
        {
            $check = \App\User::where("email", "=", $request->email)->first();
            if ($check == null)
            {
                $user = new \App\User();
                $user->name = "";
                $user->email = $request->email;
                $user->password = \Hash::make($request->password);
                $user->save();

                $achievements = \App\Achievement::all();
                foreach ($achievements as $achievement)
                {
                    $achievementTracker = new \App\AchievementTracker();
                    $achievementTracker->achievement_id = $achievement->id;
                    $achievementTracker->user_id = $user->id;
                    $achievementTracker->save();
                }

                $token = JWTAuth::fromUser($user);
                return response()->json(compact('token'));
            }
            else
            {
                return response()->json(['account_present'], 400);
            }
        }
        else
        {
            $user = $this->authService->getUser($request);
            if ($user)
            {
                return response()->json(['account_present'], 400);
            }
        }
    }

    public function getPrivateLadders(Request $request)
    {
        $ladders = $this->ladderService->getLadders(true);

        $user = $request->user();

        if ($user->isGod())
            return $ladders;

        $ladders = $ladders->filter(function ($ladder) use ($user)
        {
            return $ladder->allowedToView($user);
        });

        return $ladders->values();
    }
}
