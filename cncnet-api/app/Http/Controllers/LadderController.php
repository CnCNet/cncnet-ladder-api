<?php

namespace App\Http\Controllers;

use App\CountableObjectHeap;
use App\Http\Services\AchievementService;
use App\Http\Services\ChartService;
use \Carbon\Carbon;
use App\LadderHistory;
use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\StatsService;
use App\Player;
use App\PlayerCache;
use App\PlayerHistory;
use App\PlayerRating;
use App\User;

class LadderController extends Controller
{
    private $ladderService;
    private $statsService;
    private $chartService;
    private $achievementService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
        $this->statsService = new StatsService();
        $this->chartService = new ChartService();
        $this->achievementService = new AchievementService();
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

    public function getLadderPlayerRatings(Request $request)
    {
        $results = [];

        $history = LadderHistory::find(679);

        // $player = Player::where("id", 157968)->where("ladder_id", $history->ladder->id)->first();
        // $playerHistory = $player->calculateTier($history);
        // dd($player->rating->rating, $playerHistory);
        // dd($player->calculateTier($history));

        $players = PlayerCache::where("ladder_history_id", $history->id)->get();

        foreach ($players as $player)
        {
            # Test on kain
            // if ($player->player_id == "157968")
            // {
            $ph = $player->player->calculateTier($history);
            $player->tier = $ph->tier;
            $player->save();
            if ($ph->tier == 2)
            {
                // echo $player;
            }
            // echo $ph->tier . "\n";
            // }
            // $player->save();
        }



        // $players = Player::where("ladder_id", "=", 1)->get();
        // foreach ($players as $player)
        // {
        //     $rating = PlayerRating::where("player_id", $player->id)->first();
        //     $results[$rating->rating][$player->id] = $player->username;
        // }

        return $results;
    }

    public function getLadderIndex(Request $request)
    {
        $history = $this->ladderService->getActiveLadderByDate($request->date, $request->game);

        if ($history === null)
            abort(404);

        $tier = $request->tier ?? 1; // Default to tier 1

        # Stats
        $statsPlayerOfTheDay = $this->statsService->getPlayerOfTheDay($history);

        # Filter & Ordering
        if ($request->filterBy && $request->orderBy)
        {
            $orderBy = $request->orderBy == "desc" ? "desc" : "asc";

            $players = \App\PlayerCache::where('ladder_history_id', '=', $history->id)
                ->where('tier', $tier)
                ->where('player_name', 'like', '%' . $request->search . '%')
                ->orderBy('games', $orderBy)
                ->paginate(45);
        }
        else
        {
            # Default
            $players = \App\PlayerCache::where('ladder_history_id', '=', $history->id)
                ->where('tier', $tier)
                ->where('player_name', 'like', '%' . $request->search . '%')
                ->orderBy('points', 'desc')
                ->paginate(45);
        }

        $sides = \App\Side::where('ladder_id', '=', $history->ladder_id)
            ->where('local_id', '>=', 0)
            ->orderBy('local_id', 'asc')
            ->lists('name');

        $data = [
            "history" => $history,
            "tier" => $request->tier,
            "search" => $request->search,
            "sides" => $sides,
            "stats" => $this->statsService->getQmStats($request->game),
            "statsPlayerOfTheDay" => $statsPlayerOfTheDay,
            "players" => $players,
            "ladders" => $this->ladderService->getLatestLadders(),
            "ladders_previous" => $this->ladderService->getPreviousLaddersByGame($request->game),
            "clan_ladders" => $this->ladderService->getLatestClanLadders(),
            "games" => $this->ladderService->getRecentLadderGames($request->date, $request->game, 16),
        ];

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
            "ladders.games-listing",
            [
                "ladders" => $this->ladderService->getLatestLadders(),
                "clan_ladders" => $this->ladderService->getLatestClanLadders(),
                "history" => $this->ladderService->getActiveLadderByDate($request->date, $request->game),
                "games" => $games,
                "userIsMod" => $userIsMod,
                "errorGames" => $errorGames
            ]
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
        {
            abort(404, "Game not found");
        }

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
        {
            $gameReport = $game->allReports()->where('game_reports.id', '=', $reportId)->first();
        }
        else
        {
            $gameReport = $game->report;
        }

        $qmMatchStates = [];
        $qmConnectionStats = [];
        $qmMatchPlayers = [];

        if ($userIsMod)
        {
            $qmMatchStates = $game->qmMatch ? $game->qmMatch->states : [];
            $qmMatchPlayers = $game->qmMatch ? $game->qmMatch->players : [];
            $qmConnectionStats = $game->qmMatch ? $game->qmMatch->qmConnectionStats : [];
        }

        $playerGameReports = $gameReport->playerGameReports()->get() ?? [];
        $heaps = CountableObjectHeap::all();

        return view(
            'ladders.game-detail',
            [
                "game" => $game,
                "gameReport" => $gameReport,
                "allGameReports" => $allGameReports,
                "playerGameReports" => $playerGameReports,
                "history" => $history,
                "heaps" => $heaps,
                "user" => $user,
                "userIsMod" => $userIsMod,
                "cncnetGame" => $cncnetGame,
                "qmMatchStates" => $qmMatchStates,
                "qmConnectionStats" => $qmConnectionStats,
                "qmMatchPlayers" => $qmMatchPlayers,
                "date" => $date,
            ]
        );
    }

    public function getLadderPlayer(Request $request, $date = null, $cncnetGame = null, $username = null)
    {
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);

        $player = Player::where("ladder_id", "=", $history->ladder->id)
            ->where("username", "=", $username)
            ->first();

        if ($player == null)
        {
            abort(404, "No player found");
        }

        $user = $request->user();

        $userIsMod = false;
        if ($user !== null && $user->isLadderMod($player->ladder))
        {
            $userIsMod = true;
        }

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
            {
                $bans[] = $ban;
            }
        }
        $mod = $request->user();

        $ladderPlayer = $this->ladderService->getLadderPlayer($history, $player->username);
        $userPlayer = User::where("id", $player->user_id)->first();

        # Stats
        $graphGamesPlayedByMonth = $this->chartService->getGamesPlayedByMonth($player, $history);
        $playerFactionsByMonth = $this->statsService->getFactionsPlayedByPlayer($player, $history);
        $playerWinLossByMaps = $this->statsService->getMapWinLossByPlayer($player, $history);
        $playerGamesLast24Hours = $player->totalGames24Hours($history);
        $playerMatchups = $this->statsService->getPlayerMatchups($player, $history);
        $playerOfTheDayAward = $this->statsService->checkPlayerIsPlayerOfTheDay($history, $player);
        $recentAchievements = $this->achievementService->getRecentlyUnlockedAchievements($history, $userPlayer, 3);
        $achievementProgressCounts = $this->achievementService->getProgressCountsByUser($history, $userPlayer);

        return view(
            "ladders.player-detail",
            [
                "mod" => $mod,
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
                "playerMatchups" => $playerMatchups,
                "achievements" => $recentAchievements,
                "achievementsCount" => $achievementProgressCounts
            ]
        );
    }

    public function getPlayerAchievementsPage(Request $request, $date = null, $cncnetGame = null, $username = null)
    {
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);

        $player = Player::where("ladder_id", "=", $history->ladder->id)
            ->where("username", "=", $username)
            ->first();

        if ($player == null)
        {
            abort(404, "No player found");
        }

        $ladderPlayer = $this->ladderService->getLadderPlayer($history, $player->username);
        $userPlayer = User::where("id", $player->user_id)->first();
        $achievements = $this->achievementService->groupedByTag($history, $userPlayer);

        return view(
            "ladders.player-detail-achievements",
            [
                "userPlayer" => $userPlayer,
                "history" => $history,
                "ladderPlayer" => json_decode(json_encode($ladderPlayer)),
                "achievements" => $achievements
            ]
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
