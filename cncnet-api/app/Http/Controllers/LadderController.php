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
        $games = ["ra", "ts", "yr"];
        $prevWinners = [];
        $prevLadders = [];

        foreach ($games as $game)
        {
            $prevLadders[] = $this->ladderService->getPreviousLaddersByGame($game, 5)->splice(0,1);
        }

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
                    "players" => $this->ladderService->getLadderPlayers($history->short, $history->ladder->game, 1, false, $request->search)->splice(0,2)
                ];
            }
        }

        return view("ladders.index",
        array
        (
            "ladders" => $this->ladderService->getLatestLadders(),
            "ladders_winners" => $prevWinners
        ));
    }

    public function getLadderIndex(Request $request)
    {
        $history = $this->ladderService->getActiveLadderByDate($request->date, $request->game);
        $data = array
        (
            "ladders" => $this->ladderService->getLatestLadders(),
            "ladders_previous" => $this->ladderService->getPreviousLaddersByGame($request->game),
            "games" => $this->ladderService->getRecentLadderGames($request->date, $request->game),
            "history" => $history,
            "players" => \App\PlayerCache::where('ladder_history_id', '=', $history->id)
                                         ->where('tier', $request->tier?'=':'>', $request->tier+0)
                                         ->where('player_name', 'like', '%'.$request->search.'%')
                                         ->orderBy('points', 'desc')->paginate(45),
            "cards" => \App\Card::orderBy('id', 'asc')->lists('short'),
            "tier" => $request->tier,
            "search" => $request->search,
            "sides" => \App\Side::where('ladder_id', '=', $history->ladder_id)
                                ->where('local_id', '>=', 0)
                                ->orderBy('local_id', 'asc')->lists('name')
        );

        if ($data["history"] === null)
            abort(404);

        return view("ladders.listing", $data);
    }


    public function getLadderGames(Request $request)
    {
        return view("ladders.games",
        array
        (
            "ladders" => $this->ladderService->getLatestLadders(),
            "history" => $this->ladderService->getActiveLadderByDate($request->date, $request->game),
            "games" => $this->ladderService->getRecentLadderGamesPaginated($request->date, $request->game)
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

    public function getLadderGame(Request $request, $date = null, $cncnetGame = null, $gameId = null, $reportId = null)
    {
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);
        $game = $this->ladderService->getLadderGameById($history, $gameId);

        $user = $request->user();

        if ($game == null)
            return "No game";

        if ($user !== null && $user->isLadderMod($history->ladder))
        {
            $allGameReports = $game->allReports;
            $userIsMod = true;
        }
        else
        {
            $allGameReports = $game->report()->get();
            $userIsMod = false;
        }

        if ($reportId !== null)
            $gameReport = $game->allReports()->where('game_reports.id', '=', $reportId)->first();
        else
            $gameReport = $game->report->first();

        return view('ladders.game-view',
        array(
            "game" => $game,
            "gameReport" => $gameReport,
            "allGameReports" => $allGameReports,
            "playerGameReports" => $gameReport !== null ? $gameReport->playerGameReports()->get() : [],
            "history" => $history,
            "ladders" => $this->ladderService->getLatestLadders(),
            "heaps" => \App\CountableObjectHeap::all(),
            "user" => $user,
            "userIsMod" => $userIsMod,
            "date" => $date,
            "cncnetGame" => $cncnetGame,
        ));
    }

    public function getLadderPlayer(Request $request, $date = null, $cncnetGame = null, $username = null)
    {
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);

        $player = \App\Player::where("ladder_id", "=", $history->ladder->id)
            ->where("username", "=", $username)->first();

        if ($player == null)
            return "No Player";

        $user = $request->user();

        $userIsMod = false;
        if ($user !== null && $user->isLadderMod($player->ladder))
            $userIsMod = true;

        $games = $player->playerGames()
                ->where("ladder_history_id", "=", $history->id)
                ->orderBy('created_at', 'DESC')
                ->paginate(24);
        $playerUser = $player->user;

        return view
        (
            "ladders.player-view",
            array
            (
                "ladders" => $this->ladderService->getLatestLadders(),
                "history" => $history,
                "player" => json_decode(json_encode($this->ladderService->getLadderPlayer($history, $player->username))),
                "games" => $games,
                "userIsMod" => $userIsMod,
                "playerUser" => $playerUser,
                "ladderId" => $player->ladder->id,
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

    public function saveLadder(Request $request, $ladderId = null)
    {
        $ladder = \App\Ladder::find($ladderId);

        if ($ladderId === null || $ladder === null)
        {
            $request->session()->flash('error', 'Unabled to find ladder');
            return redirect()->back();
        }

        $ladder->name = $request->name;
        $ladder->abbreviation = $request->abbreviation;
        $ladder->game = $request->game;
        $ladder->save();

        $request->session()->flash('success', 'Ladder information saved.');

        return redirect()->back();
    }
}
