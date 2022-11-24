<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\PlayerService;
use \App\Http\Services\LadderService;
use \App\EmailVerification;
use \App\PlayerActiveHandle;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Mail;

class AccountController extends Controller
{
    private $playerService;
    private $ladderService;

    public function __construct()
    {
        $this->playerService = new PlayerService();
        $this->ladderService = new LadderService();
    }

    public function getAccountIndex(Request $request)
    {
        $user = \Auth::user();
        $user->ip_address_id = \App\IpAddress::getID(isset($_SERVER["HTTP_CF_CONNECTING_IP"])
            ? $_SERVER["HTTP_CF_CONNECTING_IP"]
            : $request->getClientIp());

        \App\IpAddressHistory::addHistory($user->id, $user->ip_address_id);
        $user->save();

        return view(
            "auth.account",
            array(
                "user" => $user,
                "userSettings" => $user->userSettings,
                "ladders" => $this->ladderService->getLatestLadders(),
                "clan_ladders" => $this->ladderService->getLatestClanLadders(),
                "private_ladders" => $this->ladderService->getLatestPrivateLadderHistory($user)
            )
        );
    }

    public function getLadderAccountIndex(Request $request, $ladderAbbrev)
    {
        $ladders = $this->ladderService->getLatestLadders();
        $clan_ladders =  $this->ladderService->getLatestClanLadders();
        $ladder = \App\Ladder::where('abbreviation', '=', $ladderAbbrev)->first();
        $user = $request->user();
        $players = $user->usernames()->where("ladder_id", '=', $ladder->id)
            ->orderBy("ladder_id", "DESC")
            ->orderBy("id", "DESC")
            ->get();

        $date = \Carbon\Carbon::now();
        $start = $date->startOfMonth()->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        $activeHandles = \App\PlayerActiveHandle::getUserActiveHandles($user->id, $start, $end)->where('ladder_id', $ladder->id)->get();

        $primaryPlayer = $activeHandles->count() > 0 ? $activeHandles->first()->player : null;

        $clanPlayers = $players->filter(function ($player)
        {
            return $player->clanPlayer !== null;
        })
            ->map(function ($player)
            {
                return $player->clanPlayer;
            });

        $invitations = $players->filter(function ($player)
        {
            return $player->clanInvitations->count() > 0;
        })
            ->map(function ($player)
            {
                return $player->clanInvitations;
            })
            ->collapse();

        return view("auth.ladder-account", compact(
            'ladders',
            'clan_ladders',
            'ladder',
            'user',
            'players',
            'activeHandles',
            'clan',
            'clanPlayers',
            'primaryPlayer',
            'invitations'
        ));
    }

    public function rename(Request $request)
    {
        $this->validate($request, ['name' => 'required|string|regex:/^[a-zA-Z0-9_\[\]\{\}\^\`\-\\x7c]+$/|max:11|unique:users']);

        $user = \App\User::find($request->id);

        if ($request->user()->id == $user->id || $request->user()->isGod())
        {
            $user->name = $request->name;
            $user->save();
            $request->session()->flash('success', "Sucessfully updated.");
            return redirect()->back();
        }
        else
        {
            $request->session()->flash('error', "Unable to change to that username.");
            return redirect()->back();
        }
    }

    public function createUsername(Request $request, $ladderAbbrev)
    {
        $this->validate($request, [
            'username' => 'required|string|regex:/^[a-zA-Z0-9_\[\]\{\}\^\`\-\\x7c]+$/|max:11', //\x7c = | aka pipe
        ]);

        $ladder = \App\Ladder::where('abbreviation', '=', $ladderAbbrev)->first();

        if ($ladder === null)
        {
            $request->session()->flash('error', 'Ladder Not Found.');
            return redirect()->back();
        }

        $user = \Auth::user();
        $player = $this->playerService->addPlayerToUserAccount($request->username, $user, $ladder->id);

        if ($player == null)
        {
            $request->session()->flash('error', 'This username has been taken');
            return redirect()->back();
        }

        // If we're creating a username for the first time for this ladder type
        $isNewUser = \App\Player::where("user_id", $user->id)
            ->where("ladder_id", $ladder->id)
            ->count();

        if ($isNewUser == 1)
        {
            PlayerActiveHandle::setPlayerActiveHandle($ladder->id, $player->id, $user->id);
        }

        if ($player == null)
        {
            $request->session()->flash('error', 'This username has been taken');
            return redirect()->back();
        }

        $request->session()->flash('success', 'Username created!');
        return redirect()->back();
    }

    public function toggleUsernameStatus(Request $request, $ladderAbbrev)
    {
        $this->validate($request, [
            'username' => 'required|string',
        ]);

        $user = \Auth::user();
        $ladder = \App\Ladder::where("abbreviation", $ladderAbbrev)->first();

        if ($user == null || $ladder == null)
        {
            return redirect()->back();
        }

        $username = $request->username;

        // Check request is linked to the auth'd user
        $player = \App\Player::where("username", "=", $username)
            ->where("ladder_id", "=", $ladder->id)
            ->where("user_id", "=", $user->id)
            ->first();

        if ($player == null)
        {
            $request->session()->flash('error', 'Unknown error');
            return;
        }

        $date = Carbon::now();
        $startOfMonth = $date->startOfMonth()->toDateTimeString();
        $endOfMonth = $date->endOfMonth()->toDateTimeString();

        // Check if there are active handles within this month
        $hasActiveHandles = PlayerActiveHandle::getUserActiveHandleCount($user->id, $ladder->id, $startOfMonth, $endOfMonth);

        // Allow TS players to have 3 nicks as opposed to just 1
        // Other games are still restricted to 1
        if ($ladder->game == "ts" && $hasActiveHandles == 3)
        {
            $request->session()->flash('error', 'You have ' . $hasActiveHandles . ' active nicks for this
            month and ladder already. If you are trying to make a username inactive, the month we are in
            has to complete first.');

            return redirect()->back();
        }
        else if ($ladder->game != "ts" && $hasActiveHandles >= 1)
        {
            // Check if there are games played on the user's active handle this month
            $hasActiveHandlesGamesPlayed = PlayerActiveHandle::getUserActiveHandleGamesPlayedCount($user->id, $ladder->id, $startOfMonth, $endOfMonth);

            if ($hasActiveHandlesGamesPlayed >= 1)
            {
                $request->session()->flash('error', 'Your active user has already played ' . $hasActiveHandlesGamesPlayed . ' games this month.
                If you are trying to make a username inactive, the month we are in has to complete first.');

                return redirect()->back();
            }
        }

        // Get the player thats being requested to change
        $activeHandle = PlayerActiveHandle::getPlayerActiveHandle($player->id, $ladder->id, $startOfMonth, $endOfMonth);

        $hasActiveHandlesGamesPlayed = PlayerActiveHandle::getUserActiveHandleGamesPlayedCount($user->id, $ladder->id, $startOfMonth, $endOfMonth);

        //allowed to remove the active handle if no games have been played yet
        if ($activeHandle != null && $hasActiveHandlesGamesPlayed < 1)
        {
            $activeHandle->delete();

            $request->session()->flash('success', $player->username . ' is now inactive');
            return redirect()->back();
        }

        // If it's not an active handle make it one
        if ($activeHandle == null)
        {
            if ($ladder->game != "ts")
            {
                //delete the player's other active handles
                PlayerActiveHandle::getUserActiveHandles($user->id, $startOfMonth, $endOfMonth)
                    ->where('ladder_id', $ladder->id)
                    ->delete();
            }

            $activeHandle = PlayerActiveHandle::setPlayerActiveHandle($ladder->id, $player->id, $user->id);

            $request->session()->flash('success', $player->username . ' is now active on the ladder.');
            return redirect()->back();
        }

        $request->session()->flash('error', $player->username . ' is still active for this month');
        return redirect()->back();
    }

    public function updatePlayerCard(Request $request, $ladderAbbrev)
    {
        $this->validate($request, [
            'cardId' => 'required|string',
            'playerId' => 'required|string'
        ]);

        $user = \Auth::user();
        $card = \App\Card::find($request->cardId);
        return $this->playerService->updatePlayerCard($user, $card, $request->playerId);
    }

    public function getNewVerification(Request $request)
    {
        return view('auth.verify');
    }

    public function createNewVerification(Request $request)
    {
        $user = $request->user();
        if ($user->email_verified)
        {
            $request->session()->flash('failure', 'Your email is already verified');
            return redirect()->back();
        }

        $user->sendNewVerification();

        $request->session()->flash('success', 'Email Verification Code Sent to ' . $user->email);
        return redirect()->back();
    }

    public function verifyEmail(Request $request, $verify_token = null)
    {
        $user = $request->user();
        $ev = EmailVerification::where('token', '=', $verify_token)->first();

        if ($ev !== null && $ev->user_id == $user->id)
        {
            $user->email_verified = true;
            $ev->delete();
            $user->save();
            $request->session()->flash('success', 'Your email has been Verified');
        }

        return redirect()->back();
    }

    public function getUserSettings(Request $request)
    {
        $user = Auth::user();
        return view(
            "auth.account-settings",
            [
                "user" => $user,
                "userSettings" => $user->userSettings,
            ]
        );
    }

    public function updateUserSettings(Request $request)
    {
        $this->validate($request, [
            "avatar" => "image|mimes:jpg,jpeg,png|max:1000",
            "discord_profile" => "string",
            "youtube_profile" => "string",
            "twitch_profile" => "string"
        ]);

        $user = Auth::user();

        # User Settings
        $userSettings = $user->userSettings;
        if ($userSettings === null)
        {
            $userSettings = new \App\UserSettings();
            $userSettings->user_id = $user->id;
        }
        $userSettings->disabledPointFilter = $request->disabledPointFilter == "on" ? true : false;
        // $userSettings->enableAnonymous = $request->enableAnonymous == "on" ? true : false; TODO later
        $userSettings->save();

        # Remove avatar?
        if ($request->removeAvatar == "on")
        {
            $user->removeAvatar();
        }

        $new_discord_profile = $request->discord_profile;

        $user_with_discord_profile = \App\User::where('discord_profile', '=', $new_discord_profile)->first();

        //if a user already has this discord profile, and the user with the discord profile is not the current user, exit
        if ($user_with_discord_profile !== null && $user->id !== $user_with_discord_profile->id)
        {
            $request->session()->flash('error', "This discord profile is already being used by another user.");
            return redirect()->back();
        }

        # Social profiles
        $user->discord_profile = $new_discord_profile;
        $user->youtube_profile = $request->youtube_profile;
        $user->twitch_profile = $request->twitch_profile;

        # User Avatar
        if ($request->hasFile("avatar"))
        {
            $avatar = Image::make($request->file('avatar')->getRealPath())->resize(300, 300)->encode("png");
            $hash = md5($avatar->__toString());
            $path = "avatars/{$user->id}/{$hash}.png";
            Storage::put($path, $avatar);

            $user->avatar_path = $path;
        }

        $user->save();

        $request->session()->flash("success", 'User settings updated!');
        return redirect()->back();
    }
}
