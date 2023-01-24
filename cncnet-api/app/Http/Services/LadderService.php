<?php

namespace App\Http\Services;

use App\Ladder;
use App\PlayerRating;
use \Illuminate\Database\Eloquent\Collection;
use \Carbon\Carbon;
use \Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class LadderService
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function getAllLadders()
    {
        $ladders = \App\Ladder::all();

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
        }
        return $ladders;
    }

    public function getLadders($private = false)
    {
        $ladders = \App\Ladder::where('private', '=', $private)->get();

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
        }
        return $ladders;
    }

    public function getLatestLadders()
    {
        return Cache::remember("ladderService::getLatestLadders", 1, function ()
        {
            $date = Carbon::now();

            $start = $date->startOfMonth()->toDateTimeString();
            $end = $date->endOfMonth()->toDateTimeString();

            return \App\LadderHistory::leftJoin("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
                ->where("ladder_history.starts", "=", $start)
                ->where("ladder_history.ends", "=", $end)
                ->whereNotNull("ladder.id")
                ->where('ladder.clans_allowed', '=', false)
                ->where('ladder.private', '=', false)
                ->orderBy('ladder.order', 'ASC')
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

        $ladderHistories = \App\LadderHistory::join("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
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
        return Cache::remember("ladderService::getLatestClanLadders", 1, function ()
        {
            $date = Carbon::now();

            $start = $date->startOfMonth()->toDateTimeString();
            $end = $date->endOfMonth()->toDateTimeString();

            return \App\LadderHistory::leftJoin("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
                ->where("ladder_history.starts", "=", $start)
                ->where("ladder_history.ends", "=", $end)
                ->whereNotNull("ladder.id")
                ->where('ladder.clans_allowed', '=', true)
                ->where('ladder.private', '=', false)
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

    public function getPreviousLaddersByGame($cncnetGame, $limit = 5)
    {
        $date = Carbon::now();

        $start = $date->startOfMonth()->subMonth($limit)->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        $ladder = \App\Ladder::where("abbreviation", "=", $cncnetGame)->first();

        if ($ladder === null) return collect();

        return \App\LadderHistory::where("ladder_history.starts", ">=", $start)
            ->where("ladder_history.ladder_id", "=", $ladder->id)
            ->limit($limit)
            ->get()
            ->reverse();
    }

    public function getActiveLadderByDate($date, $cncnetGame = null)
    {
        $date = explode("-", $date);

        if (count($date) < 2)
            return null;

        $month = $date[0];
        $year = $date[1];

        if ($month > 12 || $month < 0)
        {
            return null;
        }

        $date = Carbon::create($year, $month, 1, 0);

        $start = $date->startOfMonth()->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        if ($cncnetGame == null)
        {
            return \App\LadderHistory::where("starts", "=", $start)
                ->where("ends", "=", $end)->first();
        }
        else
        {
            $ladder = \App\Ladder::where("abbreviation", "=", $cncnetGame)->first();
            if ($ladder === null)
                return null;

            return \App\LadderHistory::where("starts", "=", $start)
                ->where("ends", "=", $end)
                ->where("ladder_id", "=", $ladder->id)
                ->first();
        }
    }

    public function getLadderByGame($game)
    {
        return \App\Ladder::where("abbreviation", "=", $game)
            ->first();
    }

    public function getLaddersByGame($game)
    {
        return \App\Ladder::where("abbreviation", "=", $game)
            ->get();
    }

    public function getLadderByGameAbbreviation($game, $limit = 25)
    {
        $ladder = $this->getLadderByGame($game);

        if ($ladder == null)
            return "No ladder found";

        $players = \App\Player::where("ladder_id", "=", $ladder->id)
            ->limit($limit)
            ->get();

        return $players;
    }

    public function getRecentLadderGames($date, $cncnetGame, $limit = 4)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);
        if ($history == null)
        {
            return [];
        }

        return \App\Game::where("ladder_history_id", "=", $history->id)
            ->whereNotNull('game_report_id')
            ->orderBy("games.id", "DESC")
            ->limit($limit)
            ->get();
    }

    public function getRecentValidLadderGames($date, $cncnetGame, $limit = 4)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);
        if ($history == null)
        {
            return [];
        }

        return \App\Game::where("ladder_history_id", "=", $history->id)
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

        return \App\Game::where("ladder_history_id", "=", $history->id)
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
            ->where('game_reports.duration', '=', 3)
            ->where('finished', '=', 1)
            ->orderBy("games.id", "DESC")
            ->paginate(45);
    }

    public function getLadderGameById($history, $gameId)
    {
        if ($history == null || $gameId == null)
            return "Invalid parameters";

        return \App\Game::where("id", "=", $gameId)->where('ladder_history_id', $history->id)->first();
    }

    public function getLadderPlayer($history, $username)
    {
        if ($history === null)
            return ["error" => "Incorrect Ladder"];

        $player = \App\Player::where("ladder_id", "=", $history->ladder->id)
            ->where("username", "=", $username)->first();

        if ($player === null)
            return ["error" => "No such player"];

        $playerCache = $player->playerCache($history->id);

        if ($playerCache == null)
        {
            return [
                "id" => $player->id,
                "player" => $player,
                "username" => $player->username,
                "points" => 0,
                "rank" => 0,
                "game_count" => 0,
                "games_won" => 0,
                "games_lost" => 0,
                "average_fps" => 0,
                "rating" => PlayerRating::$DEFAULT_RATING,
            ];
        }

        return [
            "id" => $playerCache->player_id,
            "player" => $player,
            "username" => $player->username,
            "points" => $playerCache->points,
            "rank" => $playerCache->rank(),
            "games_won" => $playerCache->wins,
            "game_count" => $playerCache->games,
            "games_lost" => $playerCache->games - $playerCache->wins,
            "average_fps" => $playerCache->fps,
            "rating" => $playerCache->rating,
        ];
    }

    public function getLadderPlayers($date, $cncnetGame, $tier = 1, $paginate = null, $search = null)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);

        if ($tier === null)
            $tier = 1;

        if ($history == null)
            return [];

        $query = \App\Player::where("ladder_id", "=", $history->ladder->id)
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

    public function undoPlayerCache($gameReport)
    {
        $history = $gameReport->game->ladderHistory;

        foreach ($gameReport->playerGameReports as $playerGR)
        {
            $player = $playerGR->player;
            $pc = \App\PlayerCache::where("ladder_history_id", '=', $history->id)
                ->where('player_id', '=', $player->id)->first();
            $pc->mark();
            $pc->points -= $playerGR->points;
            $pc->games--;
            if ($playerGR->won)
                $pc->wins--;

            $pc->save();
        }
    }

    public function updatePlayerCache($gameReport)
    {
        $history = $gameReport->game->ladderHistory;

        foreach ($gameReport->playerGameReports as $playerGR)
        {
            $player = $playerGR->player;
            $pc = \App\PlayerCache::where("ladder_history_id", '=', $history->id)
                ->where('player_id', '=', $player->id)
                ->first();

            if ($pc === null)
            {
                $pc = new \App\PlayerCache;
                $pc->ladder_history_id = $history->id;
                $pc->player_id = $player->id;
                $pc->player_name = $player->username;
                $pc->save();
            }

            $pc->mark();

            $pc->points += $playerGR->points;
            $pc->games++;
            if ($playerGR->won)
                $pc->wins++;

            $pc->save();
        }
    }


    /**
     * Return matches which have spawned in last $createdAfter minutes
     * Note: This could include matches which have finished as well.
     */
    public function getRecentSpawnedMatches($ladder_id, $createdAfter)
    {
        return \App\QmMatch::join('qm_match_states as qms', 'qm_matches.id', '=', 'qms.qm_match_id')
            ->join('state_types as st', 'qms.state_type_id', '=', 'st.id')
            ->join('qm_match_players as qmp', 'qm_matches.id', '=', 'qmp.qm_match_id')
            ->join('players as p', 'qmp.player_id', '=', 'p.id')->join('qm_maps', 'qm_matches.qm_map_id', '=', 'qm_maps.id')
            ->join('sides', function ($join)
            {
                $join->on('sides.ladder_id', '=', 'qmp.ladder_id');
                $join->on('sides.local_id', '=', 'qmp.actual_side');
            })
            ->where(function ($where)
            {
                $where->where('qms.state_type_id', 5);
            })
            ->where('qm_matches.ladder_id', $ladder_id)
            ->where('qm_matches.updated_at', '>', Carbon::now()->subMinute($createdAfter))
            ->groupBy('qmp.id')
            ->select("qm_matches.id", "qm_matches.created_at as qm_match_created_at", "sides.name as faction", "p.id as player_id", "qm_maps.description as map")
            ->get();
    }

    /**
     * Return matches which have finished in last $minutes minutes. 
     */
    public function getRecentFinishedMatches($ladder_id, $minutes)
    {
        return \App\QmMatch::join('qm_match_states as qms', 'qm_matches.id', '=', 'qms.qm_match_id')
            ->join('state_types as st', 'qms.state_type_id', '=', 'st.id')
            ->where(function ($where)
            {
                $where->where('qms.state_type_id', 1);
                $where->orWhere('qms.state_type_id', 3);
                $where->orWhere('qms.state_type_id', 6);
                $where->orWhere('qms.state_type_id', 7);
            })
            ->where('qm_matches.ladder_id', $ladder_id)
            ->where('qm_matches.updated_at', '>', Carbon::now()->subMinute($minutes))
            ->select("qm_matches.id")
            ->get();
    }
}
