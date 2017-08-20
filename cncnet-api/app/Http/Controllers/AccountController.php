<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\PlayerService;
use \App\Http\Services\LadderService;

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
            'username' => 'required|string|max:12',
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
}