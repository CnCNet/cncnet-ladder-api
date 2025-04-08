<?php

namespace App\Http\Controllers;

use App\Http\Services\LadderService;
use App\Http\Services\PlayerService;
use App\Models\EmailVerification;
use App\Models\PlayerActiveHandle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        $user->ip_address_id = \App\Models\IpAddress::getID(isset($_SERVER["HTTP_CF_CONNECTING_IP"])
            ? $_SERVER["HTTP_CF_CONNECTING_IP"]
            : $request->getClientIp());

        \App\Models\IpAddressHistory::addHistory($user->id, $user->ip_address_id);
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
        $ladder = \App\Models\Ladder::where('abbreviation', '=', $ladderAbbrev)->first();
        $user = $request->user();
        $players = $user->usernames()->where("ladder_id", '=', $ladder->id)
            ->orderBy("ladder_id", "DESC")
            ->orderBy("id", "DESC")
            ->get();

        $date = \Carbon\Carbon::now();
        $start = $date->startOfMonth()->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        $activeHandles = \App\Models\PlayerActiveHandle::getUserActiveHandles($user->id, $start, $end)->where('ladder_id', $ladder->id)->get();

        //grab active players who are not in a clan
        $activePlayersNotInAClan = [];
        foreach ($activeHandles as $activeHandle)
        {
            if ($activeHandle->player->clanPlayer == null)
            {
                $activePlayersNotInAClan[] = $activeHandle;
            }
        }

        //grab clan players
        $clanPlayers = $players->filter(function ($player)
        {
            return $player->clanPlayer !== null;
        })
            ->map(function ($player)
            {
                return $player->clanPlayer;
            });

        //grab any clan invitations
        $invitations = $players->filter(function ($player)
        {
            return $player->clanInvitations->count() > 0;
        })
            ->map(function ($player)
            {
                return $player->clanInvitations;
            })
            ->collapse();

        $myOldClans = [];

        foreach ($players as $player)
        {
            $results = \App\Models\Clan::where('ex_player_id', $player->id)->get();

            foreach ($results as $result)
            {
                $result['playerName'] = \App\Models\Player::where('id', $result->ex_player_id)->first()->username;
                $myOldClans[] = $result;
            }
        }

        return view("auth.ladder-account", compact(
            'ladders',
            'clan_ladders',
            'ladder',
            'user',
            'players',
            'activeHandles',
            'activePlayersNotInAClan',
            'myOldClans',
            'clanPlayers',
            'invitations'
        ));
    }

    public function rename(Request $request)
    {
        $this->validate($request, ['name' => 'required|string|regex:/^[a-zA-Z0-9_\[\]\{\}\^\`\-\\x7c]+$/|max:11|unique:users']);

        $user = \App\Models\User::find($request->id);

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

        $ladder = \App\Models\Ladder::where('abbreviation', '=', $ladderAbbrev)->first();

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
        $isNewUser = \App\Models\Player::where("user_id", $user->id)
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
        $ladder = \App\Models\Ladder::where("abbreviation", $ladderAbbrev)->first();
        $maxActivePlayersAllowed = $ladder->qmLadderRules->max_active_players;

        if ($user == null || $ladder == null)
        {
            return redirect()->back();
        }

        $username = $request->username;

        // Check request is linked to the auth'd user
        $player = \App\Models\Player::where("username", "=", $username)
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

        // Get the player thats being requested to change
        $activeHandle = PlayerActiveHandle::getPlayerActiveHandle($player->id, $ladder->id, $startOfMonth, $endOfMonth);

        //get count of how many games this user has played
        $hasActiveHandlesGamesPlayed = PlayerActiveHandle::getUserActiveHandleGamesPlayedCount($activeHandle, $startOfMonth, $endOfMonth);

        //allowed to remove the active handle if no games have been played yet
        if ($activeHandle != null && $hasActiveHandlesGamesPlayed < 1)
        {
            $activeHandle->delete();

            $request->session()->flash('success', $player->username . ' is now inactive');
            return redirect()->back();
        }
        else if ($activeHandle != null && $hasActiveHandlesGamesPlayed > 0)
        {
            $request->session()->flash('error', "Unable to deactive player $player->username because $player->username has played games this month already.");

            return redirect()->back();
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
                $request->session()->flash('error', "You already have an active user, deactivate that user to activate another. The maximum amount of active players is $maxActivePlayersAllowed");

                return redirect()->back();
            }

            $activeHandle = PlayerActiveHandle::setPlayerActiveHandle($ladder->id, $player->id, $user->id);

            $request->session()->flash('success', $player->username . ' is now active on the ladder. You can now play in Ranked Matches!');
            return redirect()->back();


            $request->session()->flash('error', $player->username . ' is still active for this month');
            return redirect()->back();
        }
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
            "avatar" => "nullable|image|mimes:jpg,jpeg,png,gif|max:2000",
            "discord_profile" => "nullable|string",
            "youtube_profile" => "nullable|string",
            "twitch_profile" => "nullable|string"
        ]);

        # Check if urls
        if (
            filter_var($request->youtube_profile, FILTER_VALIDATE_URL) ||
            filter_var($request->twitch_profile, FILTER_VALIDATE_URL) ||
            filter_var($request->discord_profile, FILTER_VALIDATE_URL)
        )
        {
            $request->session()->flash('error', "URL's are not allowed, enter usernames only");
            return redirect()->back();
        }

        $user = Auth::user();

        # User Settings
        $userSettings = $user->userSettings;
        if ($userSettings === null)
        {
            $userSettings = new \App\Models\UserSettings();
            $userSettings->user_id = $user->id;
        }
        $userSettings->disabledPointFilter = $request->disabledPointFilter == "on" ? true : false;
        $userSettings->do_not_match_yuri = $request->do_not_match_yuri == "on" ? true : false;
        $userSettings->skip_score_screen = $request->skip_score_screen == "on" ? true : false;
        $userSettings->match_any_map = $request->match_any_map == "on" ? true : false;
        $userSettings->is_anonymous = $request->is_anonymous == "on" ? true : false;
        $userSettings->match_ai = $request->matchAI == "on" ? true : false;
        $userSettings->is_observer = $request->isObserver == "on" ? true : false;
        $userSettings->allow_observers = $request->allowObservers == "on" ? true : false;
        $userSettings->save();

        # Remove avatar?
        if ($request->removeAvatar == "on")
        {
            $user->removeAvatar();
        }

        $newDiscordProfile = $request->discord_profile;

        $userWithDiscordProfile = \App\Models\User::where('discord_profile', '=', $newDiscordProfile)->first();

        // if a user already has this discord profile, and the user with the discord profile is not the current user, exit
        if ($userWithDiscordProfile !== null && $user->id !== $userWithDiscordProfile->id)
        {
            // Check its not just an empty string
            if (strlen($userWithDiscordProfile->discord_profile) > 0)
            {
                $request->session()->flash('error', "This discord profile is already being used by another user.");
                return redirect()->back();
            }
        }

        # Social profiles
        $user->discord_profile = $newDiscordProfile;
        $user->youtube_profile = $request->youtube_profile;
        $user->twitch_profile = $request->twitch_profile;

        # User emoji
        if ($request->user_emoji)
        {
            $user->emoji = json_encode($request->user_emoji);
        }

        if ($request->remove_emoji)
        {
            $user->emoji = null;
        }

        # User Avatar
        if ($request->hasFile("avatar"))
        {
            $file = $request->file("avatar");
            Storage::createDirectory("avatars/{$user->id}/", [
                "visibility" => "public"
            ]);
            if ($file->getClientOriginalExtension() == "gif")
            {
                $hash = md5($file->__toString());
                $path = "avatars/{$user->id}/{$hash}.gif";
                copy($file->getRealPath(), $path);
            }
            else
            {
                $avatar = Image::make($request->file('avatar')->getRealPath())->resize(300, 300)->encode("png");
                $hash = md5($avatar->__toString());
                $path = "avatars/{$user->id}/{$hash}.png";
                Storage::put($path, $avatar);
            }

            $user->avatar_path = $path;
        }

        $user->save();

        $request->session()->flash("success", 'User settings updated!');
        return redirect()->back();
    }
}
