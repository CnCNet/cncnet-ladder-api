<?php 
namespace App\Http\Controllers;

use \Carbon\Carbon;
use App\LadderHistory;
use Illuminate\Http\Request;
use \App\Http\Services\LadderService;

class LadderController extends Controller 
{
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
    }
    
    public function getBadgesIndex(Request $request)
    {
        return view("ladders.badges", 
        array
        (
            "ladders" => $this->ladderService->getLadders(),
            "ladder" => $this->ladderService->getLadderByGame($request->game))
        );
    }

    public function getLadders(Request $request)
    {
        return view("ladders.index", 
        array
        (
            "ladders" => $this->ladderService->getLadders())
        );
    }

    public function getLadderIndex(Request $request)
    {
        return view("ladders.listing", 
        array
        (
            "games" => $this->ladderService->getRecentLadderGames($request->date, $request->game),
            "ladders" => $this->ladderService->getLadders(),
            "history" => $this->ladderService->getActiveLadderByDate($request->date),
            "players" => $this->ladderService->getLadderPlayers($request->date, $request->game)
        ));
    }

    public function getLaddersByGame(Request $request)
    {
        return view("ladders.listing");
    }

    public function getLadder(Request $request, $game = null)
    {
        return $this->ladderService->getLadderByGameAbbreviation($game);
    }

    public function getLadderGame(Request $request, $date = null, $game = null, $gameId = null)
    {
        $ladder = $this->ladderService->getActiveLadderByDate($date);
        $game = $this->ladderService->getLadderGameById($ladder, $gameId);
        
        if ($game == null) return "No game";
        $stats = $game->stats()->get();

        return view('ladders.game-view', 
        array(
            "game" => $game, 
            "stats" => $stats, 
            "ladder" => $ladder,
            "ladders" => $this->ladderService->getLadders(),
        ));
    }

    public function getLadderPlayer(Request $request, $date = null, $cncnetGame = null, $player = null)
    {
        $games = [];
        $ladder = $this->ladderService->getActiveLadderByDate($date);
        $player = \App\Player::where("ladder_id", "=", $ladder->id)
            ->where("username", "=", $player)->first();

        $playerGames = $player->games()->orderBy("id", "DESC")->get();
        foreach($playerGames as $cncnetGame)
        {
            $g = $cncnetGame->game()->first();
            if ($g != null)
            {
                $games[] = $g;
            }
        }

        return view
        ( 
            "ladders.player-view", 
            array 
            (
                "ladders" => $this->ladderService->getLadders(),
                "history" => $ladder,
                "player" => json_decode(json_encode($this->ladderService->getLadderPlayer($ladder, $player->username))),
                "games" => $games,
            )
        );
    }
}