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
    
    public function getLadders(Request $request)
    {
        return view("ladders.index", 
        array
        (
            "ladders" => $this->ladderService->getLatestLadders()
        ));
    }

    public function getLadderIndex(Request $request)
    {
        return view("ladders.listing", 
        array
        (
            "ladders" => $this->ladderService->getLatestLadders(),
            "games" => $this->ladderService->getRecentLadderGames($request->date, $request->game),
            "history" => $this->ladderService->getActiveLadderByDate($request->date, $request->game),
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

    public function getLadderGame(Request $request, $date = null, $cncnetGame = null, $gameId = null)
    {
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);
        $game = $this->ladderService->getLadderGameById($history, $gameId);
        
        if ($game == null) 
            return "No game";

        $stats = $game->stats()->get();

        return view('ladders.game-view', 
        array(
            "game" => $game, 
            "stats" => $stats, 
            "history" => $history,
            "ladders" => $this->ladderService->getLatestLadders(),
        ));
    }

    public function getLadderPlayer(Request $request, $date = null, $cncnetGame = null, $username = null)
    {
        $games = [];
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);
  
        $player = \App\Player::where("ladder_id", "=", $history->ladder->id)
            ->where("username", "=", $username)->first();
      
        if ($player == null) 
            return "No Player";

        $playerGames = \App\PlayerGame::where("player_id", "=", $player->id)
            ->leftJoin("games as g", "g.id", "=", "player_games.game_id")
            ->where("g.ladder_history_id", "=", $history->id)
            ->orderBy("g.id", "DESC")
            ->get();

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
                "ladders" => $this->ladderService->getLatestLadders(),
                "history" => $history,
                "player" => json_decode(json_encode($this->ladderService->getLadderPlayer($history, $player->username))),
                "games" => $games,
            )
        );
    }
}