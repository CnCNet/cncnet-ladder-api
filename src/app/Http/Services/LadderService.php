<?php

namespace App\Http\Services;

use App\Models\Game;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Models\Ladder;
use App\Models\LadderHistory;

class LadderService
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function getLadders($private = false)
    {
        $ladders = Ladder::where('private', '=', $private)->get();

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

            return LadderHistory::leftJoin("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
                ->where("ladder_history.starts", "=", $start)
                ->where("ladder_history.ends", "=", $end)
                ->whereNotNull("ladder.id")
                ->where('ladder.clans_allowed', '=', false)
                ->where('ladder.private', '=', false)
                ->get();
        });
    }

    public function getLatestPrivateLadders($user = null)
    {
        return Cache::remember("ladderService::getLatestPrivateLadders", 1, function () use ($user)
        {
            if ($user === null)
                return collect();
            $date = Carbon::now();

            $start = $date->startOfMonth()->toDateTimeString();
            $end = $date->endOfMonth()->toDateTimeString();

            if ($user->isGod())
            {
                return LadderHistory::join("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
                    ->whereNotNull("ladder.id")
                    ->where("ladder_history.starts", "=", $start)
                    ->where("ladder_history.ends", "=", $end)
                    ->where('ladder.private', '=', 1)
                    ->get();
            }
            else
            {
                return LadderHistory::join("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
                    ->join("ladder_admins as la", "la.ladder_id", "=", "ladder.id")
                    ->whereNotNull("ladder.id")
                    ->where("ladder_history.starts", "=", $start)
                    ->where("ladder_history.ends", "=", $end)
                    ->where('ladder.private', '=', 1)
                    ->get();
            }
        });
    }

    public function getLatestClanLadders()
    {
        return Cache::remember("ladderService::getLatestClanLadders", 1, function ()
        {
            $date = Carbon::now();

            $start = $date->startOfMonth()->toDateTimeString();
            $end = $date->endOfMonth()->toDateTimeString();

            return LadderHistory::leftJoin("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
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
        return Cache::remember("ladderService::getPrivateLadders{$user->id}", 1, function () use ($user)
        {
            if ($user === null)
                return collect();

            return $user->privateLadders()->get();
        });
    }

    public function getPreviousLaddersByGame($cncnetGame, $limit = 5)
    {
        $date = Carbon::now();

        // $start = $date->startOfMonth()->subMonth($limit)->toDateTimeString();
        // Upgrade
        $start = $date->startOfMonth()->subMonth()->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        $ladder = Ladder::where("abbreviation", "=", $cncnetGame)->first();

        if ($ladder === null) return collect();

        return LadderHistory::where("ladder_history.starts", ">=", $start)
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
            return LadderHistory::where("starts", "=", $start)
                ->where("ends", "=", $end)->first();
        }
        else
        {
            $ladder = Ladder::where("abbreviation", "=", $cncnetGame)->first();
            if ($ladder === null)
                return null;

            return LadderHistory::where("starts", "=", $start)
                ->where("ends", "=", $end)
                ->where("ladder_id", "=", $ladder->id)
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

        $players = \App\Models\Player::where("ladder_id", "=", $ladder->id)
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

        return \App\Models\Game::where("ladder_history_id", "=", $history->id)
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

        return \App\Models\Game::where("ladder_history_id", "=", $history->id)
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

        return \App\Models\Game::where("ladder_history_id", "=", $history->id)
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
            ->where("ladder_history_id", "=", $history->id)
            ->where('game_reports.duration', '<=', 3)
            ->orderBy("games.id", "DESC")
            ->paginate(45);
    }

    public function getLadderGameById($history, $gameId)
    {
        if ($history == null || $gameId == null)
            return "Invalid parameters";

        return \App\Models\Game::where("id", "=", $gameId)->where('ladder_history_id', $history->id)->first();
    }

    public function getLadderPlayer($history, $username)
    {
        if ($history === null)
            return ["error" => "Incorrect Ladder"];

        $player = \App\Models\Player::where("ladder_id", "=", $history->ladder->id)
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
                "points" => 0,
                "rank" => 0,
                "game_count" => 0,
                "games_won" => 0,
                "games_lost" => 0,
                "average_fps" => 0,
                "badge" => \App\Models\Player::getBadge(0),
                "rating" => 1200,
                "percentile" => 0
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
            "badge" => \App\Models\Player::getBadge($playerCache->percentile),
            "rating" => $playerCache->rating,
            "percentile" => $playerCache->percentile,
        ];
    }

    public function getLadderPlayers($date, $cncnetGame, $tier = 1, $paginate = null, $search = null)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);

        if ($tier === null)
            $tier = 1;

        if ($history == null)
            return [];

        $query = \App\Models\Player::where("ladder_id", "=", $history->ladder->id)
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

    // TODO - should be middleware
    public function checkPlayer($request, $username, $ladder)
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
            $pc = \App\Models\PlayerCache::where("ladder_history_id", '=', $history->id)
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
            $pc = \App\Models\PlayerCache::where("ladder_history_id", '=', $history->id)
                ->where('player_id', '=', $player->id)->first();

            if ($pc === null)
            {
                $pc = new \App\Models\PlayerCache;
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
}
