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
    
    public function getLadders(Request $request)
    {
        return view("ladders.index", 
        array(
            "ladders" => $this->ladderService->getLadders())
        );
    }

    public function getLadderIndex(Request $request)
    {
        return view("ladders.listing", 
        array(
            "ladders" => $this->ladderService->getLadders(),
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
        $game = $this->ladderService->getLadderGameById($game, $gameId);
        $ladder = $this->ladderService->getLadderByGame($request->game);

        $stats = $game->stats;
        $playerStats = [];
        foreach($stats as $stat)
        {
            $player = \App\Player::where("id", "=", $stat->player_id)->first();
            $playerStats = $player->playerStats()->first();
        }

        return view('ladders.game-view', array("gameStats" => $stats, "playerStats" => $playerStats,  "ladder" => $ladder));
    }

    public function getLadderPlayer(Request $request, $game = null, $player = null)
    {
        return view
        ( "ladders.player-view", 
            array (
                "ladders" => $this->ladderService->getLadders(),
                "ladder" => $this->ladderService->getLadderByGame($request->game),
                "player" => $this->ladderService->getLadderPlayer($game, $player),
                "rank" => $this->ladderService->getLadderPlayerRank($request->game, $request->player)
            )
        );
    }
}