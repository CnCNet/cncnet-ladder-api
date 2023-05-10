<?php

namespace App\Http\Controllers;

use App\ClanPlayer;
use Illuminate\Http\Request;
use \App\Http\Services\AuthService;
use \App\Http\Services\PlayerService;
use \App\Http\Services\LadderService;
use App\Ladder;
use App\Player;
use \App\PlayerActiveHandle;
use App\User;
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

        $this->createPlayerForLaddersIfNoneExist($user);

        return $this->getActivePlayersByUser($user);
    }


    /**
     * Return user's active players
     * @param User $user
     * @return array
     */
    private function getActivePlayersByUser(User $user)
    {
        $playerList = [];

        $date = Carbon::now();
        $startOfMonth = $date->startOfMonth()->toDateTimeString();
        $endOfMonth = $date->endOfMonth()->toDateTimeString();

        $activeHandles = PlayerActiveHandle::getUserActiveHandles($user->id, $startOfMonth, $endOfMonth)->get();

        foreach ($activeHandles as $activeHandle)
        {
            // IMPORTANT: Include this $player->ladder in this check to trigger it in the response
            // No idea why.
            $ladder = $activeHandle->player->ladder;
            $player = $activeHandle->player;
            $player['user_avatar_path'] = $player->user->avatar_path;

            if ($activeHandle->player->ladder)
            {
                if ($ladder->clans_allowed)
                {
                    // Check player has clan
                    if ($player && $player->clanPlayer == null)
                    {
                        continue;
                    }
                    $clan = $player->clanPlayer->clan;
                    $player->clanPlayer;
                }
                $playerList[] = $activeHandle->player;
            }
        }

        return $playerList;
    }

    private function createPlayerForLaddersIfNoneExist(User $user)
    {
        $ladders = Ladder::getAllowedQMLaddersByUser($user);

        foreach ($ladders as $ladder)
        {
            // Do we have an active handle in this ladder?
            $hasActiveHandle = PlayerActiveHandle::getActiveMonthPlayerHandle($user->id, $ladder->id);

            if ($hasActiveHandle != null)
                // Nothing more to do.
                continue;

            // Have we had past active handles we can use this month?
            $hasPastActiveHandle = PlayerActiveHandle::getAnyPreviousPlayerHandle($user->id, $ladder->id);
            if ($hasPastActiveHandle)
            {
                // Automatically set this as our active handle
                PlayerActiveHandle::setPlayerActiveHandle(
                    $ladder->id,
                    $hasPastActiveHandle->player_id,
                    $user->id
                );
            }
            else
            {
                // We've had no past active handles
                // Find a player that a user recently owned on the ladder and use that
                $player = Player::where("user_id", $user->id)->where("ladder_id", $ladder->id)
                    ->orderBy("id", "DESC")
                    ->first();

                if ($player)
                {
                    // Use as our active handle on this ladder.
                    PlayerActiveHandle::setPlayerActiveHandle(
                        $ladder->id,
                        $player->id,
                        $user->id
                    );
                }
                else
                {
                    // Find a player that a user recently owned on ANY ladder
                    // and use that on this ladder too
                    $player = Player::where("user_id", $user->id)
                        ->orderBy("id", "DESC")
                        ->first();

                    if ($player)
                    {
                        // Check this username is not owned by someone on this ladder
                        $playerCount = Player::where("username", $player->username)
                            ->where("id", "!=", $player->id)
                            ->where('ladder_id', $ladder->id)
                            ->count();
                        if ($playerCount == 0)
                        {
                            // Use as our active handle on this ladder.
                            $this->playerService->addPlayerToUser($player->username, $user, $ladder->id);
                            continue;
                        }
                    }

                    // Last resort, failsafe
                    $this->createRandomNickForLadder($user, $ladder);
                }
            }
        }
    }

    private function createRandomNickForLadder(User $user, Ladder $ladder)
    {
        $playerUsername = "";
        $userCanCreateNick = false;

        while ($userCanCreateNick == false)
        {
            $playerUsername = $this->autoGeneratePlayerUsername();
            $existsAlready = Player::where("username", $playerUsername)->where("ladder_id", $ladder->id)->first();

            if ($existsAlready == null)
            {
                $userCanCreateNick = true;
            }
        }

        // Add new player username to user
        $this->playerService->addPlayerToUser($playerUsername, $user, $ladder->id);
    }


    private function autoGeneratePlayerUsername()
    {
        $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $randomPlayerName = "";

        for ($i = 0; $i < 9; $i++)
        {
            $index = rand(0, strlen($characters) - 1);
            $randomPlayerName .= $characters[$index];
        }

        return $randomPlayerName;
    }


    /**
     * Limit nicknames to the expired date, one per ladder type
     */
    private function getActivePlayerByLadder($ladderId, $userId)
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

        if ($tempNick == null)
        {
            // Get nick last created limited by this new 1 nick rule
            $tempNick = \App\Player::where("user_id", $userId)
                ->where('ladder_id', $ladderId)
                ->orderBy("id", "desc")
                ->first();
        }

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

                $userSettings = new \App\UserSettings();
                $userSettings->user_id = $user->id;
                $userSettings->save();

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
        $user = $request->user();
        return Ladder::getAllowedQMLaddersByUser($user);
    }
}
