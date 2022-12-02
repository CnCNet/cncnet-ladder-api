<?php

namespace App\Http\Controllers;

use \Carbon\Carbon;
use App\LadderHistory;
use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use App\Ladder;

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

        $ladder = Ladder::where("abbreviation", $game)->first();
        $prevLadders[] = $this->ladderService->getPreviousLaddersByGame($game, 10)->splice(0, 9);

        foreach ($prevLadders as $h)
        {
            foreach ($h as $history)
            {
                $prevWinners[] = [
                    "game" => $history->ladder->game,
                    "short" => $history->short,
                    "full" => $history->ladder->name,
                    "abbreviation" => $history->ladder->abbreviation,
                    "ends" => $history->ends,
                    "players" => \App\PlayerCache::where('ladder_history_id', '=', $history->id)->orderBy('points', 'desc')->get()->splice(0, 20)
                ];
            }
        }

        return view(
            "champions.index",
            [
                "ladder" => $ladder,
                "abbreviation" => $game,
                "ladders_winners" => $prevWinners,
                "ladders" => $this->ladderService->getLatestLadders(),
                "clan_ladders" => $this->ladderService->getLatestClanLadders()
            ]
        );
    }
}
