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
                "ladders" => $this->ladderService->getLadders()
            )
        );
    }

    public function createUsername(Request $request)
    {
        $this->validate($request, [
            'ladder' => 'required|string|',
            'username' => 'required|string|unique:players',
        ]);

        $user = \Auth::user(); 
        return $this->playerService->addPlayerToUser($request->username, $user, $request->ladder);
    }
}