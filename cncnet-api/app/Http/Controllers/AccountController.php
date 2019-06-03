<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\PlayerService;
use \App\Http\Services\LadderService;
use \App\EmailVerification;
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

        $user = \Auth::user();
        $username = $this->playerService->addPlayerToUser($request->username, $user, $request->ladder);

        if ($username == null)
        {
             $request->session()->flash('error', 'This username has been taken');
        }
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

        // Delete old verification table entry
        $old = EmailVerification::where('user_id', '=', $user->id)->get();
        foreach ($old as $v)
        {
            $v->delete();
        }

        // Create a new confirmation entry
        $ev = new EmailVerification;
        $ev->user_id = $user->id;
        $ev->token = hash('sha256', rand(0, getrandmax()).$user->email);
        $ev->save();

        $email = $user->email;
        // Email new confirmation
        Mail::send('emails.verification', ['token' => $ev->token ], function($message) use ($email)
        {
            $message->to($email)->subject('Email verification for CnCNet Ladder');
        });

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