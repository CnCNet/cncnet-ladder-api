<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\PlayerService;
use \App\Http\Services\LadderService;

class AdminController extends Controller 
{
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();  
    }

    public function getAdminIndex(Request $request)
    {
        return view("admin.index", ["ladders" => $this->ladderService->getLadders()]);
    }

    public function getLadderSetupIndex(Request $request)
    {
        return view("admin.ladder-setup", ["ladders" => $this->ladderService->getLadders()]);
    }

    public function getManageUsersIndex(Request $request)
    {
        return view("admin.manage-users", ["ladders" => $this->ladderService->getLadders()]);
    }

    public function getManageGameIndex(Request $request, $cncnetGame = null)
    {
        $ladder = \App\Ladder::where("abbreviation", "=", $cncnetGame)->first();

        if ($ladder == null) 
            return "No ladder";
        
        $games = \App\Game::where("ladder_id", "=", $ladder->id)->orderBy("id", "DESC");
        return view("admin.manage-games", ["games" => $games, "ladder" => $ladder]);
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