<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\PlayerService;
use \App\Http\Services\LadderService;

class AdminController extends Controller 
{
    public function __construct()
    {
      
    }

    public function getAdminIndex(Request $request, $cncnetGame = null)
    {
        $ladder = \App\Ladder::where("abbreviation", "=", $cncnetGame)->first();

        if ($ladder == null) 
            return "No ladder";
        
        $games = \App\Game::where("ladder_id", "=", $ladder->id)->orderBy("id", "DESC");
        return view("admin.index", ["games" => $games, "ladder" => $ladder]);
    }

    public function deleteGame(Request $request)
    {
        $game = \App\Game::find($request->game_id);
        if ($game == null) return "Game not found";

        $playerGames = \App\PlayerGame::where("game_id", "=", $game->id)->get();
        foreach($playerGames as $pg)
        {
            $pg->delete();
        }

        $playerPoints = \App\PlayerPoint::where("game_id", "=", $game->id)->get();
        foreach($playerPoints as $pp)
        {
            $pp->delete();
        }

        $ladderGames = \App\LadderGame::where("game_id", "=", $game->id)->get();
        foreach($ladderGames as $lg)
        {
            $lg->delete();
        }

        $game->delete();

        return redirect()->back();
    }
}