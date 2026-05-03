<?php

namespace App\Http\Controllers;

use App\Helpers\GameHelper;
use App\Http\Services\AchievementService;
use App\Http\Services\ChartService;
use App\Http\Services\LadderService;
use App\Http\Services\StatsService;
use App\Models\Clan;
use App\Models\ClanCache;
use App\Models\CountableObjectHeap;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Player;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use \App\Models\PlayerActiveHandle;

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

        // Note: These services are instantiated here for backward compatibility.
        // Ideally, they should be constructor-injected using Laravel's dependency injection.
        // The GetPlayerDetailAction already uses proper constructor injection.
    }

    public function getLadders(Request $request)
    {
        return view(
            "ladders.index",
            [
                "ladders" => $this->ladderService->getLatestLadders(),
                "clan_ladders" => $this->ladderService->getLatestClanLadders(),
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

        if (!isset($history))
        {
            abort(404, "No ladder history found");
        }

        $history->load([
            'ladder',
            'ladder.qmLadderRules',
            'ladder.sides',
        ]);


        $tier = isset($request->tier) && !empty($request->tier) ? $request->tier : 1; // Default to tier 1

        if (!$history->ladder->clans_allowed)
        {
            $players = $this->ladderService->getPlayersFromCacheForLadderHistory(
                ladderHistory: $history,
                filterBy: $request->filterBy,
                orderBy: $request->orderBy,
                tier: $tier,
                search: $request->search
            );

            $mostUsedFactions = $this->ladderService->getMostUsedFactionForPlayerCachesInLadderHistory($history, $players->getCollection());
            $ranks = $this->ladderService->getPlayerRanksForLadderHistory($history, request()->tier ?? 1);
        }
        else
        {
            $clans = $this->ladderService->getClansFromCacheForLadderHistory(
                $history,
                $request->filterBy,
                $request->orderBy,
                $request->search
            );
            $mostUsedFactions = $this->ladderService->getMostUsedFactionForClanCachesInLadderHistory($history, $clans->getCollection());
            $ranks = $this->ladderService->getClanRanksForLadderHistory($history);
        }

        $games = $this->ladderService->getRecentLadderGames($history, 16);
        $ladderHistoriesPrevious = $this->ladderService->getPreviousLadderHistoryForLadder($history->ladder);

        $data = [
            'history' => $history,
            'ladderHistoriesPrevious' => $ladderHistoriesPrevious,
            'stats' => $this->statsService->getQmStats($history),
            'statsXOfTheDay' => $this->statsService->getWinnerOfTheDay($history),
            'players' => $players ?? null,
            'clans' => $clans ?? null,
            'games' => $games,
            'ranks' => $ranks,
            'mostUsedFactions' => $mostUsedFactions ?? [],
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

    /**
     * Display game detail page
     *
     * @param Request $request
     * @param string|null $date Ladder history date (format: M-YYYY)
     * @param string|null $cncnetGame Game abbreviation (e.g., 'yr', 'ra2')
     * @param int|null $gameId Game ID
     * @param int|null $reportId Optional specific report ID
     * @return \Illuminate\View\View
     */
    public function getLadderGame(
        Request $request,
        ?string $date = null,
        ?string $cncnetGame = null,
        ?int $gameId = null,
        ?int $reportId = null
    ) {
        $action = app(\App\Actions\Game\GetGameDetailAction::class);

        $viewData = $action->execute(
            date: $date,
            cncnetGame: $cncnetGame,
            gameId: $gameId,
            reportId: $reportId,
            authenticatedUser: $request->user()
        );

        // Determine which view to render based on ladder type
        $viewName = $viewData['history']->ladder->clans_allowed
            ? 'ladders.clan-game-detail'
            : 'ladders.game-detail';

        return view($viewName, $viewData);
    }

    /**
     * Display player detail page
     *
     * @param Request $request
     * @param string|null $date Ladder history date (format: M-YYYY)
     * @param string|null $cncnetGame Game abbreviation (e.g., 'yr', 'ra2')
     * @param string|null $username Player username
     * @return \Illuminate\View\View
     */
    public function getLadderPlayer(
        Request $request,
        ?string $date = null,
        ?string $cncnetGame = null,
        ?string $username = null
    ) {
        $action = app(\App\Actions\Player\GetPlayerDetailAction::class);

        $viewData = $action->execute(
            date: $date,
            cncnetGame: $cncnetGame,
            username: $username,
            authenticatedUser: $request->user()
        );

        return view('ladders.player-detail', $viewData);
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
        $clanPlayerWinLossByMonth = $this->statsService->getClanPlayerWinLosses($clan, $history);

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
                "clanGamesLast24Hours" => $clanGamesLast24Hours,
                "clanPlayerWinLossByMonth" => $clanPlayerWinLossByMonth
            ]
        );
    }

    public function getCanceledMatches($ladderAbbreviation = null)
    {
        $ladder = \App\Models\Ladder::where('abbreviation', $ladderAbbreviation)->first();

        if ($ladder == null)
        {
            abort(404, "Ladder not found");
        }

        /**
         * Using denormalized data - no complex joins needed!
         * Data is stored directly in qm_canceled_matches table when created.
         * Falls back to legacy query if denormalized fields are null (backward compatibility).
         */
        $matches = \App\Models\QmCanceledMatch::where('ladder_id', $ladder->id)
            ->where('created_at', '>=', Carbon::now()->subWeek()) // Show last week of data
            ->whereNotNull('player_data') // Filter out rows with null player data
            ->whereRaw('JSON_LENGTH(player_data) > 0') // Filter out empty JSON arrays
            ->orderBy('created_at', 'DESC')
            ->select([
                'id',
                'created_at',
                'qm_match_id',
                'map_name as map',
                'canceled_by_usernames as canceled_by',
                'affected_player_usernames as affected_players',
                'player_data',
                'reason'
            ])
            ->paginate(20);

        return view("admin.canceled-matches", [
            "canceled_matches" => $matches,
            "ladder" => $ladder
        ]);
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
        $ladder = \App\Models\Ladder::find($ladderId);

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
        $ladder = \App\Models\Ladder::find($ladderId);

        if ($request->id === "new")
        {
            $ladder = new \App\Models\Ladder;
            $ladder->ladder_type = Ladder::ONE_VS_ONE;
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
        if ($request->ladder_type)
        {
            $ladder->ladder_type = $request->ladder_type;
        }
        $ladder->save();

        if ($request->id === "new")
        {
            $this->addLadder($ladder->id);
        }

        $request->session()->flash('success', 'Ladder information saved.');

        return redirect("/admin/setup/{$ladder->id}/edit");
    }
}
