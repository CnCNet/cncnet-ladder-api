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
            "ladders_previous" => $this->ladderService->getPreviousLaddersByGame($request->game),
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

        $playerGames = $player->playerGameReports()
            ->leftJoin("games as g", "g.id", "=", "game_id")
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

    public function addLadder()
    {
        $ladder = \App\Ladder::where("name", "=", "Red Alert")->first();
        for($times = 0; $times < 5; $times++)
        {
            $year = 2017 + $times;
            for($month = 0; $month <= 12; $month++)
            {
                $date = Carbon::create($year, 01, 01, 0)->addMonth($month);
                $start = $date->startOfMonth()->toDateTimeString();
                $ends = $date->endOfMonth()->toDateTimeString();

                $ladderHistory = LadderHistory::where("starts", "=", $start)
                    ->where("ends", "=", $ends)
                    ->where("ladder_id", "=", $ladder->id)
                    ->first();

                if ($ladderHistory == null)
                {
                    $ladderHistory = new LadderHistory();
                    $ladderHistory->ladder_id = $ladder->id;
                    $ladderHistory->starts = $start;
                    $ladderHistory->ends = $ends;
                    $ladderHistory->short = $date->month . "-" . $date->year;
                    $ladderHistory->save();
                }
            }
        }
    }
}