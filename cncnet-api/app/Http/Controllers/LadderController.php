<?php

namespace App\Http\Controllers;

use App\Http\Services\ChartService;
use \Carbon\Carbon;
use App\LadderHistory;
use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\StatsService;
use App\User;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class LadderController extends Controller
{
    private $ladderService;
    private $statsService;
    private $chartService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->statsService = new StatsService();
        $this->chartService = new ChartService();
    }

    public function getLadders(Request $request)
    {
        return view(
            "ladders.index",
            [
                "ladders" => $this->ladderService->getLatestLadders(),
                "clan_ladders" => $this->ladderService->getLatestClanLadders()
            ]
        );
    }

    public function getLadderIndex(Request $request)
    {
        $history = $this->ladderService->getActiveLadderByDate($request->date, $request->game);

        if ($history === null)
            abort(404);

        $user = $request->user();
        $userIsMod = $user != null && $user->isLadderMod($history->ladder);

        # Stats
        $statsPlayerOfTheDay = $this->statsService->getPlayerOfTheDay($history);

        # Filter & Ordering
        if ($request->filterBy && $request->orderBy)
        {
            $orderBy = $request->orderBy == "desc" ? "desc" : "asc";

            $players = \App\PlayerCache::where('ladder_history_id', '=', $history->id)
                ->where('tier', $request->tier ? '=' : '>', $request->tier + 0)
                ->where('player_name', 'like', '%' . $request->search . '%')
                ->orderBy('games', $orderBy)
                ->paginate(45);
        }
        else
        {
            # Default
            $players = \App\PlayerCache::where('ladder_history_id', '=', $history->id)
                ->where('tier', $request->tier ? '=' : '>', $request->tier + 0)
                ->where('player_name', 'like', '%' . $request->search . '%')
                ->orderBy('points', 'desc')
                ->paginate(45);
        }

        $data = array(
            "stats" => $this->statsService->getQmStats($request->game),
            "statsPlayerOfTheDay" => $statsPlayerOfTheDay,
            "ladders" => $this->ladderService->getLatestLadders(),
            "ladders_previous" => $this->ladderService->getPreviousLaddersByGame($request->game),
            "clan_ladders" => $this->ladderService->getLatestClanLadders(),
            "games" => $this->ladderService->getRecentLadderGames($request->date, $request->game, 16),
            "history" => $history,
            "players" => $players,
            "userIsMod" => $userIsMod,
            "cards" => \App\Card::orderBy('id', 'asc')->lists('short'),
            "tier" => $request->tier,
            "search" => $request->search,
            "sides" => \App\Side::where('ladder_id', '=', $history->ladder_id)
                ->where('local_id', '>=', 0)
                ->orderBy('local_id', 'asc')->lists('name')
        );

        return view("ladders.listing", $data);
    }

    public function getLadderGames(Request $request)
    {
        $history = $this->ladderService->getActiveLadderByDate($request->date, $request->game);

        if ($history === null)
            abort(404);

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
        else
        {
            $games = $this->ladderService->getRecentLadderGamesPaginated($request->date, $request->game);
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
                "heaps" => \App\CountableObjectHeap::all(),
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
        $userPlayer = User::where("id", $player->user_id)->first();

        # Stats
        $graphGamesPlayedByMonth = $this->chartService->getGamesPlayedByMonth($player, $history);
        $playerFactionsByMonth = $this->statsService->getFactionsPlayedByPlayer($player, $history);
        $playerWinLossByMaps = $this->statsService->getMapWinLossByPlayer($player, $history);
        $playerGamesLast24Hours = $player->totalGames24Hours($history);

        # Awards
        $playerOfTheDayAward = $this->statsService->checkPlayerIsPlayerOfTheDay($history, $player);

        # Achievements
        $achievementProgress = $this->ladderService->getAchievementProgress($history->ladder_id, $player->user->id);

        return view(
            "ladders.player-detail",
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
                "graphGamesPlayedByMonth" => $graphGamesPlayedByMonth,
                "playerFactionsByMonth" => $playerFactionsByMonth,
                "playerGamesLast24Hours" => $playerGamesLast24Hours,
                "playerWinLossByMaps" => $playerWinLossByMaps,
                "playerOfTheDayAward" => $playerOfTheDayAward,
                "userPlayer" => $userPlayer,
                "playerGamesLast24Hours" => $playerGamesLast24Hours,
                "achievementProgress" => $achievementProgress,
                "achievements" => $history->ladder->achievements
            )
        );
    }

    public function addLadder($ladderId)
    {
        $ladder = \App\Ladder::find($ladderId);

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
        $ladder = \App\Ladder::find($ladderId);

        if ($request->id === "new")
        {
            $ladder = new \App\Ladder;
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
