<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\AuthService;
use \App\Http\Services\PlayerService;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Log;
use Carbon\Carbon;

class ApiUserController extends Controller
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function getAccount(Request $request)
    {
        $auth = $this->authService->getUser($request);

        $playerService = new \App\Http\Services\PlayerService;

        $user = $auth["user"];

        if ($auth["user"] === null)
            return response($auth["response"], $auth['status']);

        foreach (\App\Ladder::all() as $ladder)
        {
            $players = $user->usernames()->where('ladder_id', '=', $ladder->id)->get();
            if ($players->count() < 1)
            {
                // Auto-register a player for each ladder if there isn't already a player registered for this user

                $playerCreated = false;
                $oLadders = \App\Ladder::where('game', '=', $ladder->game)->where('id', '<>', $ladder->id)->get();
                foreach ($oLadders as $other)
                {
                    $oPlayers = $other->players()->where('user_id', '=', $user->id)->get();
                    foreach ($oPlayers as $op)
                    {
                        $playerService->addPlayerToUser($op->username, $user, $ladder->id);
                        $playerCreated = true;
                    }
                }
                if (!$playerCreated)
                {
                    $playerService->addPlayerToUser($user->name, $user, $ladder->id);
                }
            }
        }

        return \App\Player::where('user_id', '=', $auth["user"]->id)
            ->get();
    }

    public function tests()
    {
        // Current month
        // if they did choose a nick for a month 
        // then use this nick as the default for next month

        // $auth = $this->authService->getUser($request);
        $user = \App\User::find(19);
        if ($user == null)
        {
            return [];
        }

        // Current handle
        $activeHandles = \App\PlayerActiveHandle::where("user_id", $user->id)->get();
        $players = [];
        foreach($activeHandles as $activeHandle)
        {
            $players[] = $activeHandle->player;
        }

        // If they haven't selected a nickname
        // Get their last created
        if (count($players) == 0)
        {
            return $this->getTempNicks();
        }

        return $players;
    }

    /**
     * Returns a SINGLE nickname for all 3 ladder types
     */
    private function getTempNicks()
    {
        $tempNicks = [];
        foreach (\App\Ladder::all() as $ladder)
        {
            $tempNick = $this->getTempNickByLadderType($ladder->id);
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
    private function getTempNickByLadderType($ladderId)
    {
        $limitLatestNickByDate = Carbon::create("2019", "08", "06");

        $tempNick = \App\Player::where('user_id', '=', 1)
            ->where("created_at", "<=", $limitLatestNickByDate)
            ->where("ladder_id", $ladderId)
            ->orderBy('id', 'desc')
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

        if($token == null)
        {
            $check = \App\User::where("email", "=", $request->email)->first();
            if($check == null)
            {
                $user = new \App\User();
                $user->name = "";
                $user->email = $request->email;
                $user->password = \Hash::make($request->password);
                $user->save();

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
            if($user)
            {
                return response()->json(['account_present'], 400);
            }
        }
    }
}
