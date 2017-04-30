<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;

class LadderController extends Controller 
{
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
    }
    
    public function getLadderIndex(Request $request)
    {
        return view("ladders.index", 
        array(
            "ladder" => $this->ladderService->getLadderByGame($request->game),
            "players" => $this->ladderService->getLadderPlayers($request->game, $request->player))
        );
    }

    public function getLaddersByGame(Request $request)
    {
        return view("ladders.listing");
    }

    public function getLadder(Request $request, $game = null)
    {
        return $this->ladderService->getLadderByGameAbbreviation($game);
    }

    public function getLadderGame(Request $request, $game = null, $gameId = null)
    {
        return view('ladders.listing', $this->ladderService->getLadderGameById($game, $gameId));
    }

    public function getLadderPlayer(Request $request, $game = null, $player = null)
    {
        return view
        ( "ladders.player-view", 
            array (
                "ladder" => $this->ladderService->getLadderByGame($request->game),
                "player" =>$this->ladderService->getLadderPlayer($game, $player)
            )
        );
    }
}