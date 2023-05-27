<?php

namespace App\Http\Controllers;

use App\Clan;
use App\ClanCache;
use App\CountableObjectHeap;
use App\Game;
use App\Helpers\GameHelper;
use App\Http\Services\AchievementService;
use App\Http\Services\ChartService;
use \Carbon\Carbon;
use App\LadderHistory;
use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use App\Http\Services\UserRatingService;
use \App\Http\Services\StatsService;
use App\Ladder;
use App\Player;
use App\PlayerHistory;
use App\QmCanceledMatch;
use App\User;
use App\UserRating;
use GuzzleHttp\Subscriber\History;

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

    public function getPopularTimes(Request $request)
    {
        $now = Carbon::now();
        $start = $now->copy()->subMonth(1)->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $ladders = Ladder::whereIn(
            "abbreviation",
            [
                GameHelper::$GAME_BLITZ,
                GameHelper::$GAME_RA,
                GameHelper::$GAME_RA2,
                GameHelper::$GAME_YR,
                GameHelper::$GAME_TS
            ]
        )->get();

        foreach ($ladders as $ladder)
        {
            $histories = LadderHistory::where("starts", ">=", $start)
                ->where("ends", "<=", $end)
                ->where("ladder_id", $ladder->id)
                ->get();

            $data = $this->chartService->getHistoriesGamesPlayedByMonth($histories, $ladder->id);
            $labels = $data["labels"];
            $graphGamesPlayedByMonth[$ladder->abbreviation][] = $data["games"];
        }

        return view(
            "ladders.play",
            [
                "ladder" => $ladder,
                "labels" => $labels,
                "games" => $graphGamesPlayedByMonth,
            ]
        );
    }

    public function getLadderIndex(Request $request)
    {
        $history = $this->ladderService->getActiveLadderByDate($request->date, $request->game);

        if ($history === null)
            abort(404);

        $players = null;
        $clans = null;
        $tier = $request->tier ?? 1; // Default to tier 1


        # Filter & Ordering
        if ($request->filterBy && $request->orderBy)
        {
            $orderBy = $request->orderBy == "desc" ? "desc" : "asc";

            if ($history->ladder->clans_allowed)
            {
                $clans = ClanCache::where("ladder_history_id", "=", $history->id)
                    ->where("clan_name", "like", "%" . $request->search . "%")
                    ->orderBy("points", "desc")
                    ->orderBy("games", $orderBy)
                    ->paginate(45);
            }
            else
            {
                $players = \App\PlayerCache::where("ladder_history_id", "=", $history->id)
                    ->where("tier", "=", $tier)
                    ->where("player_name", "like", "%" . $request->search . "%")
                    ->orderBy("games", $orderBy)
                    ->paginate(45);
            }
        }
        else
        {
            if ($history->ladder->clans_allowed)
            {
                $clans = ClanCache::where("ladder_history_id", "=", $history->id)
                    ->where("clan_name", "like", "%" . $request->search . "%")
                    ->orderBy("points", "desc")
                    ->paginate(45);
            }
            else
            {
                $players = \App\PlayerCache::where("ladder_history_id", "=", $history->id)
                    ->where("tier", "=", $tier)
                    ->where("player_name", "like", "%" . $request->search . "%")
                    ->orderBy("points", "desc")
                    ->paginate(45);
            }
        }

        # Stats
        if ($history->ladder->clans_allowed)
        {
            $statsXOfTheDay = $this->statsService->getClanOfTheDay($history);
        }
        else
        {
            $statsXOfTheDay = $this->statsService->getPlayerOfTheDay($history);
        }

        $sides = \App\Side::where('ladder_id', '=', $history->ladder_id)
            ->where('local_id', '>=', 0)
            ->orderBy('local_id', 'asc')
            ->lists('name');

        $data = [
            "isClanLadder" => $history->ladder->clans_allowed,
            "players" => $players,
            "clans" => $clans,
            "history" => $history,
            "tier" => $request->tier,
            "search" => $request->search,
            "sides" => $sides,
            "stats" => $this->statsService->getQmStats($request->game),
            "statsXOfTheDay" => $statsXOfTheDay,
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

        if ($user !== null && $user->isLadderMod($history->ladder))
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

        // Tests
        if ($request->reunRewardPoints && $history->ladder->clans_allowed)
        {
            // $api = new ApiLadderController();
            // $ladderService = new LadderService;
            // $ladderService->updateCache($game->report);

            // foreach ($game->playerGameReports as $k => $pgr)
            // {
            //     $gameReport = $pgr->gameReport;
            //     $result = $api->awardClanPoints($gameReport, $history);
            //     break;
            // }
        }

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

        if ($history->ladder->clans_allowed)
        {

            $clans = [];
            foreach ($playerGameReports as $pgr)
            {
                $clans[$pgr->clan_id][] = $pgr;
            }

            $orderedClanReports = [];
            foreach ($clans as $clanId => $pgrArr)
            {
                foreach ($pgrArr as $pgr)
                {
                    $orderedClanReports[] = $pgr;
                }
            }

            $clanGameReports = $gameReport->playerGameReports()->groupBy("clan_id")->get();
            return view(
                'ladders.clan-game-detail',
                [
                    "game" => $game,
                    "gameReport" => $gameReport,
                    "allGameReports" => $allGameReports,
                    "clanGameReports" => $clanGameReports,
                    "orderedClanReports" => $orderedClanReports,
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
        else
        {
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
    }

    public function getLadderPlayer(Request $request, $date = null, $cncnetGame = null, $username = null)
    {
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);

        if ($history == null)
        {
            abort(404, "Ladder not found");
        }

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
        $userTier = $player->getCachedPlayerTierByLadderHistory($history);

        # Stats
        $graphGamesPlayedByMonth = $this->chartService->getPlayerGamesPlayedByMonth($player, $history);
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
                "userTier" => $userTier,
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

    public function getLadderClan(Request $request, $date = null, $cncnetGame = null, $clanNameShort = null)
    {
        $history = $this->ladderService->getActiveLadderByDate($date, $cncnetGame);

        if ($history == null)
        {
            abort(404, "Ladder not found");
        }

        $clan = Clan::where("ladder_id", "=", $history->ladder->id)
            ->where("short", "=", $clanNameShort)
            ->first();

        if ($clan == null)
        {
            abort(404, "No clan found");
        }

        $user = $request->user();

        $userIsMod = false;
        if ($user !== null && $user->isLadderMod($clan->ladder))
        {
            $userIsMod = true;
        }

        $clanCache = ClanCache::where("ladder_history_id", $history->id)
            ->where("clan_id", $clan->id)
            ->first();

        $games = 0;

        if ($clanCache != null)
        {
            $games = $clanCache->clan->clanGames()
                ->where("ladder_history_id", "=", $history->id)
                ->orderBy('created_at', 'DESC')
                ->paginate(24);
        }
        else
        {
            $clanCache = new ClanCache();
            $clanCache->ladder_history_id = $history->id;
            $clanCache->clan_id = $clan->id;
            $clanCache->clan_name = $clan->short;
            $clanCache->save();
        }

        $mod = $request->user();
        $userIsMod = false;

        if ($user !== null && $user->isLadderMod($history->ladder))
        {
            $userIsMod = true;
        }

        $clanPlayers = $clan->clanPlayers;

        // $ladderPlayer = $this->ladderService->getLadderPlayer($history, $player->username);

        # Stats
        $graphGamesPlayedByMonth = $this->chartService->getClanGamesPlayedByMonth($clan, $history);
        // $playerWinLossByMaps = $this->statsService->getMapWinLossByPlayer($player, $history);
        $clanGamesLast24Hours = $clan->totalGames24Hours($history);
        // $playerMatchups = $this->statsService->getPlayerMatchups($player, $history);
        // $playerOfTheDayAward = $this->statsService->checkPlayerIsPlayerOfTheDay($history, $player);
        // $recentAchievements = $this->achievementService->getRecentlyUnlockedAchievements($history, $userPlayer, 3);
        // $achievementProgressCounts = $this->achievementService->getProgressCountsByUser($history, $userPlayer);

        return view(
            "ladders.clan-detail",
            [
                "ladderPlayer" => null,
                "history" => $history,
                "clanCache" => $clanCache,
                "clanPlayers" => $clanPlayers,
                "games" => $games,
                "userIsMod" => $userIsMod,
                "graphGamesPlayedByMonth" => $graphGamesPlayedByMonth,
                "clanGamesLast24Hours" => $clanGamesLast24Hours
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
