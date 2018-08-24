<?php
namespace App\Http\Controllers;

use \Carbon\Carbon;
use App\LadderHistory;
use Illuminate\Http\Request;
use \App\Http\Services\LadderService;

class LeagueChampionsController extends Controller
{
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
    }

    public function getLeagueChampions(Request $request, $game)
    {
        $prevWinners = [];
        $prevLadders = [];

        $prevLadders[] = $this->ladderService->getPreviousLaddersByGame($game, 5)->splice(0,9);

        foreach ($prevLadders as $h)
        {
            foreach($h as $history)
            {
                $prevWinners[] = [
                    "game" => $history->ladder->game,
                    "short" => $history->short,
                    "full" => $history->ladder->name,
                    "abbreviation" => $history->ladder->abbreviation,
                    "ends" => $history->ends,
                    "players" => $this->ladderService->getLadderPlayers($history->short, $history->ladder->game, 1, false, false)->splice(0,10)
                ];
            }
        }

        return view("champions.index",
        array
        (
            "ladders_winners" => $prevWinners,
            "ladders" => $this->ladderService->getLatestLadders()
        ));
    }
}