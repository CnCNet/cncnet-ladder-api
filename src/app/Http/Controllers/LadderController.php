<?php

namespace App\Http\Controllers;

use \Carbon\Carbon;
use LadderHistory;
use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\StatsService;
use Illuminate\Support\Facades\Cache;
use App\Models\Ladder;


class LadderController extends Controller
{
    private $ladderService;
    private $statsService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->statsService = new StatsService();
    }

    public function getLadders(Request $request)
    {
        $games = ["ra", "ts", "yr"];
        $prevWinners = [];
        $prevLadders = [];

        foreach ($games as $game)
        {
            $prevLadders[] = $this->ladderService->getPreviousLaddersByGame($game, 5)->splice(0, 1);
        }

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
                    "players" => \App\Models\PlayerCache::where('ladder_history_id', '=', $history->id)->orderBy('points', 'desc')->get()->splice(0, 2)
                ];
            }
        }

        $user = $request->user();
        $userIsMod = $user != null && $user->isLadderMod($history->ladder);

        return view(
            "ladders.index",
            array(
                "ladders" => $this->ladderService->getLatestLadders(),
                "ladders_winners" => $prevWinners,
                "userIsMod" => $userIsMod,
                "clan_ladders" => $this->ladderService->getLatestClanLadders()
            )
        );
    }

    public function getLadderIndex(Request $request)
    {
        $history = $this->ladderService->getActiveLadderByDate($request->date, $request->game);

        if ($history === null)
            abort(404);

        $user = $request->user();
        $userIsMod = $user != null && $user->isLadderMod($history->ladder);

        $sides = \App\Models\Side::where('ladder_id', '=', $history->ladder_id)
            ->where('local_id', '>=', 0)
            ->orderBy('local_id', 'asc')
            ->select('name')
            ->get()
            ->toArray();

        $cards = \App\Models\Card::orderBy('id', 'asc')->select('short')->get()->toArray();

        $players = \App\Models\PlayerCache::where('ladder_history_id', '=', $history->id)
            ->where('tier', $request->tier ? '=' : '>', $request->tier + 0)
            ->where('player_name', 'like', '%' . $request->search . '%')
            ->orderBy('points', 'desc')
            ->paginate(45);

        $data = array(
            "stats" => $this->statsService->getQmStats($request->game),
            "ladders" => $this->ladderService->getLatestLadders(),
            "ladders_previous" => $this->ladderService->getPreviousLaddersByGame($request->game),
            "clan_ladders" => $this->ladderService->getLatestClanLadders(),
            "games" => $this->ladderService->getRecentLadderGames($request->date, $request->game),
            "history" => $history,
            "players" => $players,
            "userIsMod" => $userIsMod,
            "cards" => $cards,
            "tier" => $request->tier,
            "search" => $request->search,
            "sides" => $sides
        );

        return view("ladders.listing", $data);
    }


    public function getLadderGames(Request $request)
    {
        $history = $this->ladderService->getActiveLadderByDate($request->date, $request->game);

        if ($history === null)
        {
            abort(404);
        }

        $user = $request->user();
        $userIsMod = false;

        if ($user !== null && $user->isModerator())
        {
            $userIsMod = true;
        }

        $errorGames = $request->errorGames;


        if ($errorGames && $userIsMod == false)
        {
            return redirect("/ladder/" . $history->short . "/" . $history->ladder->abbreviation . "/games"); //user is not a moderator, return to games page
        }
        else if ($errorGames)
        {
            $games = $this->ladderService->getRecentErrorLadderGamesPaginated($request->date, $request->game);
        }

        return view(
            "ladders.games",
            array(
                "ladders" => $this->ladderService->getLatestLadders(),
                "clan_ladders" => $this->ladderService->getLatestClanLadders(),
                "history" => $this->ladderService->getActiveLadderByDate($request->date, $request->game),
                "games" => $games,
                "userIsMod" => $userIsMod,
                "errorGames" => $errorGames
            )
        );
    }

    /**
     * Return all games that did not load, duration = 3 seconds
     */
    public function getRecentErrorLadderGamesPaginated($date, $cncnetGame)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);
        if ($history == null)
        {
            return [];
        }

        return \App\Game::join('game_reports', 'games.game_report_id', '=', 'game_reports.id')
            ->select(
                'games.id',
                'games.ladder_history_id',
                'wol_game_id',
                'bamr',
                'games.created_at',
                'games.updated_at',
                'crat',
                'cred',
                'shrt',
                'supr',
                'unit',
                'plrs',
                'scen',
                'hash',
                'game_report_id',
                'qm_match_id'
            )
            ->where("ladder_history_id", "=", $history->id)
            ->where(function ($query)
            {
                $query->where('game_reports.duration', '<=', 3)
                    ->orWhere('game_reports.fps', '<=', 10);
            })
            ->where('finished', '=', 1)
            ->orderBy("games.id", "DESC")
            ->paginate(45);
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
            $gameReport = $game->report;

        $qmMatchStates = [];
        $qmConnectionStats = [];
        $qmMatchPlayers = [];
        if ($userIsMod)
        {
            $qmMatchStates = $game->qmMatch ? $game->qmMatch->states : [];
            $qmMatchPlayers = $game->qmMatch ? $game->qmMatch->players : [];
            $qmConnectionStats = $game->qmMatch ? $game->qmMatch->qmConnectionStats : [];
        }

        return view(
            'ladders.game-view',
            array(
                "game" => $game,
                "gameReport" => $gameReport,
                "allGameReports" => $allGameReports,
                "playerGameReports" => $gameReport !== null ? $gameReport->playerGameReports()->get() : [],
                "history" => $history,
                "ladders" => $this->ladderService->getLatestLadders(),
                "clan_ladders" => $this->ladderService->getLatestClanLadders(),
                "heaps" => \App\Models\CountableObjectHeap::all(),
                "user" => $user,
                "userIsMod" => $userIsMod,
                "date" => $date,
                "cncnetGame" => $cncnetGame,
                "qmMatchStates" => $qmMatchStates,
                "qmConnectionStats" => $qmConnectionStats,
                "qmMatchPlayers" => $qmMatchPlayers,
            )
        );
    }

    public function getLadderPlayer(Request $request, $date = null, $cncnetGame = null, $username = null)
    {
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);

        $player = \App\Models\Player::where("ladder_id", "=", $history->ladder->id)
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

        $bans = [];
        $alerts = [];
        if ($user && ($playerUser->id == $user->id || $userIsMod))
        {
            $alerts = $player->alerts;
            $ban = $playerUser->getBan();
            if ($ban)
                $bans[] = $ban;
        }
        $mod = $request->user();

        $ladderPlayer = $this->ladderService->getLadderPlayer($history, $player->username);

        return view(
            "ladders.player-view",
            array(
                "mod" => $mod,
                "ladders" => $this->ladderService->getLatestLadders(),
                "clan_ladders" => $this->ladderService->getLatestClanLadders(),
                "history" => $history,
                "ladderPlayer" => json_decode(json_encode($ladderPlayer)),
                "player" => $ladderPlayer['player'],
                "games" => $games,
                "userIsMod" => $userIsMod,
                "playerUser" => $playerUser,
                "ladderId" => $player->ladder->id,
                "alerts" => $alerts,
                "bans" => $bans,
            )
        );
    }

    public function addLadder($ladderId)
    {
        $ladder = Ladder::find($ladderId);

        for ($times = 0; $times < 5; $times++)
        {
            $year = Carbon::Now()->year + $times;
            for ($month = 0; $month <= 12; $month++)
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
        $ladder = Ladder::find($ladderId);

        if ($request->id === "new")
        {
            $ladder = new Ladder;
        }

        else if ($ladderId === null || $ladder === null)
        {
            $request->session()->flash('error', 'Unabled to find ladder');
            return redirect()->back();
        }

        $ladder->name = $request->name;
        $ladder->abbreviation = $request->abbreviation;
        $ladder->game = $request->game;
        $ladder->clans_allowed = $request->clans_allowed;
        $ladder->game_object_schema_id = $request->game_object_schema_id;
        $ladder->private = $request->private;
        $ladder->save();

        if ($request->id === "new")
        {
            $this->addLadder($ladder->id);
        }

        $request->session()->flash('success', 'Ladder information saved.');

        return redirect("/admin/setup/{$ladder->id}/edit");
    }
}
