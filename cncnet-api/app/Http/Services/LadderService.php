<?php

namespace App\Http\Services;

use App\Http\Controllers\RankingController;
use App\Models\ClanCache;
use App\Models\Game;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\PlayerCache;
use App\Models\PlayerRating;
use App\Models\Side;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Carbon\Exceptions\UnitException;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use App\Models\PlayerGameReport;
use App\Models\Stats2;
use App\Models\UrlHelper;
use App\Models\QmMatchPlayer;
use App\Models\QmMatch;
use App\Models\Player;
use App\Models\UserRating;

class LadderService
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function getAllLadders()
    {
        $ladders = Ladder::all();

        foreach ($ladders as $ladder)
        {
            $ladder["sides"] = $ladder->sides()->get();
            $rules = $ladder->qmLadderRules;

            if ($rules !== null)
            {
                $ladder["vetoes"] = $rules->map_vetoes;
                $ladder["allowed_sides"] = array_map('intval', explode(',', $rules->allowed_sides));
            }
            $current = $this->getActiveLadderByDate(Carbon::now()->format('m-Y'), $ladder->abbreviation);
            if ($current !== null)
                $ladder["current"] = $current->short;

            if ($ladder->mapPool)
                $ladder->mapPool->tiers;
        }
        return $ladders;
    }

    public function getLadders($private = false)
    {
        $ladders = \App\Models\Ladder::where('private', '=', $private)
            ->with(['sides'])
            ->get();

        foreach ($ladders as $ladder)
        {
            $ladder["sides"] = $ladder->sides;
            $rules = $ladder->qmLadderRules;

            if ($rules !== null)
            {
                $ladder["vetoes"] = $rules->map_vetoes;
                $ladder["allowed_sides"] = array_map('intval', explode(',', $rules->allowed_sides));
            }
            $current = $this->getActiveLadderByDate(Carbon::now()->format('m-Y'), $ladder->abbreviation);
            if ($current !== null)
                $ladder["current"] = $current->short;
        }
        return $ladders;
    }

    public function getLatestLadders($ladderType = null)
    {
        return Cache::remember("ladderService::getLatestLadders" . (isset($ladderType) ? $ladderType : ''), 1 * 60, function () use ($ladderType)
        {
            $date = Carbon::now();

            $start = $date->startOfMonth()->toDateTimeString();
            $end = $date->endOfMonth()->toDateTimeString();

            return LadderHistory::leftJoin("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
                ->where("ladder_history.starts", "=", $start)
                ->where("ladder_history.ends", "=", $end)
                ->whereNotNull("ladder.id")
                ->when(isset($ladderType), function ($query) use ($ladderType) { $query->where("ladder_type", "=", $ladderType); })
                ->where('ladder.clans_allowed', '=', false)
                ->where('ladder.private', '=', false)
                ->orderBy('ladder.order', 'ASC')
                ->with('ladder')
                ->get();
        });
    }

    /**
     * Returns ladder history 
     * @param mixed $user 
     * @return array 
     */
    public function getLatestPrivateLadderHistory($user)
    {
        $date = Carbon::now();

        $start = $date->startOfMonth()->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        $ladderHistories = LadderHistory::join("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
            ->whereNotNull("ladder.id")
            ->where("ladder_history.starts", "=", $start)
            ->where("ladder_history.ends", "=", $end)
            ->where('ladder.private', true)
            ->get();

        $allowedLadderHistory = [];
        foreach ($ladderHistories as $ladderHistory)
        {
            if (!$user->isLadderTester($ladderHistory->ladder))
            {
                if (!$user->isLadderAdmin($ladderHistory->ladder))
                {
                    continue;
                }
            }

            $allowedLadderHistory[] = $ladderHistory;
        }
        return $allowedLadderHistory;
    }

    public function getLatestClanLadders()
    {
        return Cache::remember("ladderService::getLatestClanLadders", -1 * 60, function ()
        {
            $date = Carbon::now();

            $start = $date->startOfMonth()->toDateTimeString();
            $end = $date->endOfMonth()->toDateTimeString();

            return LadderHistory::leftJoin("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
                ->where("ladder_history.starts", "=", $start)
                ->where("ladder_history.ends", "=", $end)
                ->whereNotNull("ladder.id")
                ->where('ladder.clans_allowed', '=', true)
                ->with('ladder')
                ->get();
        });
    }

    public function getPrivateLadders($user = null)
    {
        if ($user == null)
        {
            return collect();
        }

        return collect(Ladder::getAllowedQMLaddersByUser($user, true));
    }

    public function getPreviousLadderHistoryForLadder(Ladder $ladder, $limit = 5)
    {
        $histories = LadderHistory::where("ladder_history.starts", "<=", now()->startOfMonth()->subMonth())
            ->where("ladder_history.ladder_id", "=", $ladder->id)
            ->limit($limit)
            ->select(['short', 'starts'])
            ->orderBy('ladder_history.starts', 'DESC')
            ->get();
        $histories->each(fn($h) => $h->setRelation('ladder', $ladder));
        return $histories;
    }

    public function getPlayerRanksForLadderHistory(LadderHistory $ladderHistory, int $tier = 1): array
    {
        $players = PlayerCache::where('ladder_history_id', '=', $ladderHistory->id)
            ->where("tier", $tier)
            ->orderBy('points', 'desc')
            ->select('id')
            ->get();

        $count = 1;
        $ranks = [];
        foreach ($players as $player)
        {
            $ranks[$player->id] = $count++;
        }
        return $ranks;
    }

    public function getClanRanksForLadderHistory(LadderHistory $ladderHistory): array
    {
        $clans = ClanCache::where('ladder_history_id', '=', $ladderHistory->id)
            ->orderBy('points', 'desc')
            ->select('id')
            ->get();

        $count = 1;
        $ranks = [];
        foreach ($clans as $clan)
        {
            $ranks[$clan->id] = $count++;
        }
        return $ranks;
    }

    public function getMostUsedFactionForPlayerCachesInLadderHistory(LadderHistory $ladderHistory, Collection $players): array
    {
        $playerSides = $players->pluck($ladderHistory->ladder->game == 'yr' ? 'country' : 'side', 'id')->toArray();
        $neededSides = array_unique(array_values($playerSides));

        $sides = Side::query()
            ->where('ladder_id', $ladderHistory->ladder->id)
            ->whereIn('local_id', $neededSides)
            ->pluck('name', 'local_id')
            ->toArray();

        $out = [];
        foreach ($playerSides as $playerId => $playerSide)
        {
            $out[$playerId] = $sides[$playerSide] ?? 0;
        }
        return $out;
    }

    public function getMostUsedFactionForClanCachesInLadderHistory(LadderHistory $ladderHistory, Collection $clans): array
    {
        $clanSides = $clans->pluck($ladderHistory->ladder->game == 'yr' ? 'country' : 'side', 'id')->toArray();
        $neededSides = array_unique(array_values($clanSides));

        $sides = Side::query()
            ->where('ladder_id', $ladderHistory->ladder->id)
            ->whereIn('local_id', $neededSides)
            ->pluck('name', 'local_id')
            ->toArray();

        $out = [];
        foreach ($clanSides as $clanId => $clanSide)
        {
            $out[$clanId] = $sides[$clanSide] ?? 0;
        }
        return $out;
    }

    public function getPlayersFromCacheForLadderHistory(
        LadderHistory $ladderHistory,
        ?string $filterBy = null,
        ?string $orderBy = 'desc',
        int $tier = 1,
        int $paginateCount = 45,
        ?string $search = null
    )
    {

        return PlayerCache::query()
            ->where("ladder_history_id", "=", $ladderHistory->id)
            ->where("tier", "=", $tier)
            ->when(
                isset($search) && !empty($search),
                function ($q) use ($search)
                {
                    return $q->where("player_name", "like", "%" . $search . "%");
                },
            )
            ->when(
                $filterBy == 'games',
                function ($q) use ($orderBy)
                {
                    return $q->orderBy("games", $orderBy ?? 'desc');
                },
                function ($q)
                {
                    return $q->orderBy("points", "desc");
                }
            )
            ->with([
                'player',
                'player.user',
            ])
            ->paginate($paginateCount);
    }

    public function getClansFromCacheForLadderHistory(
        LadderHistory $ladderHistory,
        ?string $filterBy = null,
        ?string $orderBy = 'desc',
        ?string $search = null
    )
    {

        return ClanCache::query()
            ->where("ladder_history_id", "=", $ladderHistory->id)
            ->when(
                isset($search) && !empty($search),
                function ($q) use ($search)
                {
                    return $q->where("clan_name", "like", "%" . $search . "%");
                },
            )
            ->when(
                $filterBy == 'games',
                function ($q) use ($orderBy)
                {
                    return $q->orderBy("games", $orderBy ?? 'desc');
                },
                function ($q)
                {
                    return $q->orderBy("points", "desc");
                }
            )
            ->with(['clan'])
            ->paginate(45);
    }

    public function getPreviousLaddersByGame(Ladder $ladder, $limit = 5)
    {
        $date = Carbon::now();

        $start = $date->startOfMonth()->subMonth($limit)->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        if ($ladder === null) return collect();

        $histories = LadderHistory::where("ladder_history.starts", ">=", $start)
            ->where("ladder_history.ladder_id", "=", $ladder->id)
            ->limit($limit)
            ->get()
            ->reverse();

        $histories->each(fn($h) => $h->setRelation('ladder', $ladder));

        return $histories;
    }

    public function getActiveLadderByDate($date, $cncnetGame = null)
    {
        $dateParts = explode("-", $date);

        if (count($dateParts) < 2)
        {
            return null;
        }

        $month = (int) $dateParts[0];
        $year = (int) $dateParts[1];

        if ($month > 12 || $month < 1)
        {
            return null;
        }

        if ($cncnetGame === null)
        {
            return LadderHistory::whereMonth("starts", $month)
                ->whereYear("starts", $year)
                ->first();
        }
        else
        {
            return LadderHistory::whereMonth("starts", $month)
                ->whereYear("starts", $year)
                ->whereHas('ladder', function ($q) use ($cncnetGame) {
                    $q->where('abbreviation', $cncnetGame);
                })
                ->first();
        }
    }

    public function getLadderByGame($game)
    {
        return Ladder::where("abbreviation", "=", $game)
            ->first();
    }

    public function getLaddersByGame($game)
    {
        return Ladder::where("abbreviation", "=", $game)
            ->get();
    }

    public function getLadderByGameAbbreviation($game, $limit = 25)
    {
        $ladder = $this->getLadderByGame($game);

        if ($ladder == null)
            return "No ladder found";

        $players = Player::where("ladder_id", "=", $ladder->id)
            ->limit($limit)
            ->get();

        return $players;
    }

    public function getRecentLadderGames(LadderHistory $history, $limit = 4)
    {
        return Game::where("ladder_history_id", "=", $history->id)
            ->whereNotNull('game_report_id')
            ->orderBy("games.id", "DESC")
            ->limit($limit)
            ->with([
                'report',
                'report.playerGameReports.player',
                'report.playerGameReports.player.qmPlayer',
                'report.playerGameReports.clan',
                'report.playerGameReports.stats',
                'qmMatch.map.map',
                'qmMatch.players'
            ])
            ->when(
                $history->ladder->clans_allowed,
                fn($q) => $q->with([]),
                fn($q) => $q->with([]),
            )
            ->get();
    }

    public function getRecentValidLadderGames($date, $cncnetGame, $limit = 4)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);
        if ($history == null)
        {
            return [];
        }

        return Game::where("ladder_history_id", "=", $history->id)
            ->join("game_reports as gr", "gr.game_id", "=", "games.id")
            ->whereNotNull('game_report_id')
            ->where("gr.valid", "=", true)
            ->where("gr.best_report", "=", true)
            ->select("games.*")
            ->orderBy("games.id", "DESC")
            ->limit($limit)
            ->get();
    }

    public function getRecentLadderGamesPaginated($date, $cncnetGame)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);
        if ($history == null)
        {
            return [];
        }

        return Game::where("ladder_history_id", "=", $history->id)
            ->whereNotNull('game_report_id')
            ->orderBy("games.id", "DESC")
            ->paginate(45);
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

        return Game::join('game_reports', 'games.game_report_id', '=', 'game_reports.id')
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
            ->where('game_reports.duration', '=', 3)
            ->where('finished', '=', 1)
            ->orderBy("games.id", "DESC")
            ->paginate(45);
    }

    /**
     * Returns formatted results for Sneers Elo app
     * @param string $date 
     * @param string $cncnetGame 
     * @param string|array|null $requestQuery 
     * @return array 
     * @throws InvalidFormatException 
     * @throws UnitException 
     * @throws InvalidArgumentException 
     * @throws RuntimeException 
     */
    public function getGamesFormattedForEloService(string $date, string $cncnetGame, string|array|null $requestQuery, int $paginateCount = 200)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);
        $games = Game::where("ladder_history_id", "=", $history->id)
            ->whereNotNull('game_report_id')
            ->orderBy("games.id", "DESC")
            ->paginate($paginateCount)
            ->appends($requestQuery);

        $results = [];
        foreach ($games as $game)
        {
            $playerGameReports = PlayerGameReport::where('game_report_id', $game->game_report_id)->get();
            $gameUrl = URLHelper::getGameUrl($history, $game->id);
            $timestamp = $game->updated_at->timestamp;

            foreach ($playerGameReports as $playerGameReport)
            {
                if ($playerGameReport->stats)
                {
                    $playerStats2 = Stats2::where("id", $playerGameReport->stats->id)->first();
                    $playerCountry = $playerStats2->faction($history->ladder, $playerGameReport->stats->cty);

                    $won = $playerGameReport->won == true;
                    $points = $playerGameReport->points;

                    $results[$game->id][] = [
                        "points" => $points,
                        "playerCountry" => $playerCountry,
                        "playerWon" => $won,
                        "playerUsername" => $playerGameReport->player->username,
                        "gameId" => $game->id,
                        "timestamp" => $timestamp,
                        "map" => $game->qmMatch?->map?->map->name ?? $game->scen,
                        "duration" => $game->report->duration,
                        "durationFormatted" => gmdate('H:i:s', $game->report->duration),
                        "played" => $game->updated_at,
                        "playedFormatted" => $game->updated_at->diffForHumans(),
                        "fps" => $game->report->fps,
                        "gameUrl" => $gameUrl
                    ];
                }
            }
        }

        $response = [
            'current_page' => $games->currentPage(),
            'data' => $results,
            'first_page_url' => $games->url(1),
            'from' => $games->firstItem(),
            'last_page' => $games->lastPage(),
            'last_page_url' => $games->url($games->lastPage()),
            'next_page_url' => $games->nextPageUrl(),
            'path' => $games->path(),
            'per_page' => $games->perPage(),
            'prev_page_url' => $games->previousPageUrl(),
            'to' => $games->lastItem(),
            'total' => $games->total(),
        ];

        return $response;
    }

    public function getLadderGameById($history, $gameId)
    {
        if ($history == null || $gameId == null)
            return "Invalid parameters";

        return Game::where("id", "=", $gameId)->where('ladder_history_id', $history->id)->first();
    }

    public function getLadderPlayer($history, $username)
    {
        if ($history === null)
            return ["error" => "Incorrect Ladder"];

        $player = Player::where("ladder_id", "=", $history->ladder->id)
            ->where("username", "=", $username)
            ->first();

        if ($player === null)
            return ["error" => "No such player"];

        $playerCache = $player->playerCache($history->id);
        if ($playerCache == null)
        {
            return [
                "id" => $player->id,
                "player" => $player,
                "username" => $player->username,
                "alias" => "",
                "points" => 0,
                "rank" => 0,
                "game_count" => 0,
                "games_won" => 0,
                "games_lost" => 0,
                "average_fps" => 0,
                "elo" => null,
                "last_five_games" => [],
                "last_active" => null,
                "user_since" => null,
            ];
        }

        $isAnonymous = $player->user->userSettings->getIsAnonymousForLadderHistory($history);

        $last24HoursGames = $player->totalGames24Hours($history);
        $lastActive = $player->lastActive($history);
        $lastFiveGames = $player->lastFiveGames($history);

        $user = $player->user;
        $rating = $user->getEffectiveUserRatingForLadder($history->ladder->id);

        // Do not show default rating.
        if ($rating->rated_games == 0)
        {
            $rating = null;
        }

        $userSince = null;
        if (!$isAnonymous)
        {
            $userSince = $player->user->userSince();
        }

        return [
            "id" => $playerCache->player_id,
            "player" => $player,
            "username" => $player->username,
            "alias" => $isAnonymous ? "" : (empty($player->user->alias()) ? "-" : $player->user->alias()),
            "points" => $playerCache->points,
            "rank" => $playerCache->rank(),
            "games_won" => $playerCache->wins,
            "game_count" => $playerCache->games,
            "games_lost" => $playerCache->games - $playerCache->wins,
            "average_fps" => $playerCache->fps,
            "games_last_24_hours" => $last24HoursGames,
            "last_active" => $lastActive,
            "last_five_games" => $lastFiveGames,
            "elo" => $isAnonymous ? null : $rating,
            "user_since" => $userSince
        ];
    }

    public function getLadderPlayers($date, $cncnetGame, $tier = 1, $paginate = null, $search = null)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);

        if ($tier === null)
            $tier = 1;

        if ($history == null)
            return [];

        $query = Player::where("ladder_id", "=", $history->ladder->id)
            ->join('player_game_reports as pgr', 'pgr.player_id', '=', 'players.id')
            ->join('game_reports', 'game_reports.id', '=', 'pgr.game_report_id')
            ->join('games', 'games.id', '=', 'game_reports.game_id')
            ->join('player_histories as ph', 'ph.player_id', '=', 'players.id');

        if ($search)
        {
            $query->where('players.username', 'LIKE', "%{$search}%");
        }

        $query->where("games.ladder_history_id", "=", $history->id)
            ->where('game_reports.valid', true)
            ->where('game_reports.best_report', true)
            ->where('ph.ladder_history_id', '=', $history->id)
            ->where('ph.tier', '=', $tier)
            ->groupBy("players.id")
            ->select(
                \DB::raw("SUM(pgr.points) as points"),
                \DB::raw("COUNT(games.id) as total_games"),
                \DB::raw("SUM(pgr.won) as total_wins"), // TODO
                "players.*"
            )
            ->orderBy("points", "DESC");

        if ($paginate)
        {
            return $query->paginate(45);
        }

        return $query->get();
    }

    public function checkPlayer($request)
    {
        $authUser = $this->authService->getUser($request);

        if ($authUser["user"] === null)
            return $authUser["response"];
        else
            return null;
    }

    public function undoCache($gameReport)
    {
        $history = $gameReport->game->ladderHistory;

        foreach ($gameReport->playerGameReports as $playerGR)
        {
            if ($history->ladder->clans_allowed)
            {
                $clan = $playerGR->clan;
                $pc = ClanCache::where("ladder_history_id", '=', $history->id)
                    ->where('clan_id', '=', $clan->id)
                    ->first();
            }
            else
            {
                $player = $playerGR->player;
                $pc = PlayerCache::where("ladder_history_id", '=', $history->id)
                    ->where('player_id', '=', $player->id)
                    ->first();
            }

            $pc->mark();
            $pc->points -= $playerGR->points;
            $pc->games--;
            if ($playerGR->won)
                $pc->wins--;

            $pc->save();
        }
    }

    public function updateCache($gameReport)
    {
        $history = $gameReport->game->ladderHistory;

        if ($history->ladder->clans_allowed)
        {
            try
            {
                # Group playerGameReports by clan id, so we only update each clan once
                # Grab the winning team, then opposite clan must be loser
                $winningTeam = $gameReport->playerGameReports()->groupBy("clan_id")
                    ->where("won", true)
                    ->first();

                $losingTeam = $gameReport->playerGameReports()->groupBy("clan_id")
                    ->where("clan_id", "!=", $winningTeam->clan_id)
                    ->first();

                $this->saveClanCache($winningTeam, $history);
                Log::info("Updating clanCache for Clan (Winners): " . $winningTeam->clan->short);

                $this->saveClanCache($losingTeam, $history);
                Log::info("Updating clanCache for Clan (Losers): " . $losingTeam->clan->short);
            }
            catch (Exception $ex)
            {
                Log::info("ERROR ** Updating clan cache: " . $ex->getMessage());
            }
        }
        else
        {
            Log::info("Updating PlayerCache for gameReportId=$gameReport->id");

            foreach ($gameReport->playerGameReports as $playerGameReport)
            {
                $this->savePlayerCache($playerGameReport, $history);
            }
        }
    }

    private function saveClanCache($playerGameReport, $history)
    {
        $player = $playerGameReport->player;
        $clan = $player->clanPlayer->clan;

        $clanCache = ClanCache::where("ladder_history_id", '=', $history->id)
            ->where('clan_id', '=', $clan->id)
            ->first();

        if ($clanCache === null)
        {
            $clanCache = new ClanCache();
            $clanCache->ladder_history_id = $history->id;
            $clanCache->clan_id = $clan->id;
            $clanCache->clan_name = $clan->short;
            $clanCache->save();
        }

        $clanCache->mark();

        $clanCache->points += $playerGameReport->points;
        $clanCache->games++;
        if ($playerGameReport->won)
            $clanCache->wins++;

        $clanCache->save();
    }

    private function savePlayerCache($playerGameReport, $history)
    {
        $player = $playerGameReport->player;

        $pc = PlayerCache::where("ladder_history_id", '=', $history->id)
            ->where('player_id', '=', $player->id)
            ->first();

        if ($pc === null)
        {
            $pc = new PlayerCache;
            $pc->ladder_history_id = $history->id;
            $pc->player_id = $player->id;
            $pc->player_name = $player->username;
            $pc->save();
        }

        $pc->mark();

        $pc->points += $playerGameReport->points;
        $pc->games++;
        if ($playerGameReport->won)
            $pc->wins++;

        $pc->save();
    }


    /**
     * Return player and map data pertaining to a quick_match_id
     */
    public function getQmMatchPlayersInMatch($qmMatchId)
    {
        return QmMatchPlayer::join('qm_matches', 'qm_matches.id', '=', 'qm_match_players.qm_match_id')
            ->join('players as p', 'qm_match_players.player_id', '=', 'p.id')
            ->join('sides', function ($join)
            {
                $join->on('sides.ladder_id', '=', 'qm_match_players.ladder_id');
                $join->on('sides.local_id', '=', 'qm_match_players.actual_side');
            })
            ->where('qm_matches.id', $qmMatchId)
            ->groupBy('qm_match_players.id')
            ->select(
                "qm_matches.id",
                "p.username as name",
                "qm_matches.created_at as qm_match_created_at",
                "qm_match_players.team as team",
                "sides.name as faction",
                "p.id as player_id",
                "qm_match_players.clan_id as clan_id"
            )
            ->get();
    }

    /**
     * Return matches which have spawned in last $createdAfter minutes but have not finished
     */
    public function getRecentSpawnedMatches($ladder_id, $createdAfter)
    {
        return QmMatch::join('qm_match_states as qms', 'qm_matches.id', '=', 'qms.qm_match_id')
            ->join('state_types as st', 'qms.state_type_id', '=', 'st.id')
            ->join('qm_maps', 'qm_matches.qm_map_id', '=', 'qm_maps.id')
            ->where('qm_matches.ladder_id', $ladder_id)
            ->where('qm_matches.created_at', '>', Carbon::now()->subMinute($createdAfter))
            ->whereHas('states', function ($where)
            {
                $where->where('qms.state_type_id', 5); # Game Spawned

            })->whereNotIn('qm_matches.id', function ($where)
            {
                $where->select('qms.qm_match_id')->from('qm_match_states as qms')->whereIn('qms.state_type_id', [1, 6, 7]);      # Finished, GameCrashed, NotReady
            })
            ->groupBy('qm_matches.id')
            ->select("qm_matches.id as id", "qm_matches.created_at as qm_match_created_at", "qm_maps.description as map")
            ->get();
    }
}
