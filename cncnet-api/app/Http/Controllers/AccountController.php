<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\PlayerService;
use \App\Http\Services\LadderService;
use \App\EmailVerification;
use Carbon\Carbon;
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

        return view("auth.account",
            array (
                "user" => $user,
                "ladders" => $this->ladderService->getLatestLadders()
            )
        );
    }

    public function createUsername(Request $request)
    {
        $this->validate($request, [
            'ladder' => 'required|string|',
            'username' => 'required|string|regex:/^[a-zA-Z0-9_\[\]\{\}\^\`\-\\x7c]+$/|max:11', //\x7c = | aka pipe
        ]);
        
        $ladderId = $request->ladder;
        $user = \Auth::user();
        $player = $this->playerService->addPlayerToUser($request->username, $user, $ladderId);

        if ($player == null)
        {
             $request->session()->flash('error', 'This username has been taken');
            return redirect("/account");
        }

        // If we're creating a username for the first time for this ladder type
        $isNewUser = \App\Player::where("user_id", $user->id)
            ->where("ladder_id", $ladderId)
            ->count();

        if ($isNewUser == 1)
        {
            // Make it active by default
            $activeHandle = new \App\PlayerActiveHandle();
            $activeHandle->ladder_id = $ladderId;
            $activeHandle->player_id = $player->id;
            $activeHandle->user_id = $user->id;
            $activeHandle->save();
        }
        
        if ($player == null)
        {
             $request->session()->flash('error', 'This username has been taken');
            return redirect("/account");
        }

        $request->session()->flash('success', 'Username created!');
        return redirect("/account");
    }

    public function toggleUsernameStatus(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
            'ladderId' => 'required|string'
        ]);
        
        $user = \Auth::user();
        if ($user == null)
        {
            return redirect("/account");
        }

        $ladderId = $request->ladderId;
        $username = $request->username;
        
        // Check request is linked to the auth'd user
        $player = \App\Player::where("username", "=", $username)
            ->where("ladder_id", "=", $ladderId)
            ->where("user_id", "=", $user->id)
            ->first();
            
        if ($player == null)
        {
            $request->session()->flash('error', 'Unknown error');
            return;
        }

        $date = Carbon::now();
        $endOfMonth = $date->endOfMonth()->toDateTimeString();

        // Check if there are active handles within this month 
        $hasActiveHandles = \App\PlayerActiveHandle::where("ladder_id", $ladderId)
            ->where("user_id", $user->id)
            ->where("created_at", "<=", $endOfMonth)
            ->first();

        if ($hasActiveHandles)
        {
            $request->session()->flash('error', 'You have a username active for this month and ladder already. 
                If you are trying to make a username inactive, 
                the month we are in has to complete first.');
            return redirect("/account");
        }
        

        // Get the player thats being requested to change
        $activeHandle = \App\PlayerActiveHandle::where("player_id", $player->id)
            ->where("ladder_id", $ladderId)
            ->first();


        // If it's an active handle make it one
        if ($activeHandle == null)
        {
            $activeHandle = new \App\PlayerActiveHandle();
            $activeHandle->ladder_id = $ladderId;
            $activeHandle->player_id = $player->id;
            $activeHandle->user_id = $user->id;
            $activeHandle->save();

            $request->session()->flash('success', $player->username . ' is now active on the ladder.');
            return redirect("/account");
        }

        if ($activeHandle->created_at > $endOfMonth)
        {
            $activeHandle->delete();

            $request->session()->flash('success', $player->username . ' is now inactive');
            return redirect("/account");
        }

        $request->session()->flash('error', $player->username . ' is still active for this month');
        return redirect("/account");
    }

    public function updatePlayerCard(Request $request)
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
            return redirect('/account');
        }

        $user->sendNewVerification();

        $request->session()->flash('success', 'Email Verification Code Sent to '.$user->email);
        return redirect('/account');
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

        return redirect('/account');
    }

}