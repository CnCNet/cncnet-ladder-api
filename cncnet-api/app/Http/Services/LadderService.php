<?php namespace App\Http\Services;

use \Illuminate\Database\Eloquent\Collection;
use \Carbon\Carbon;
use \Illuminate\Pagination\LengthAwarePaginator;

class LadderService
{
    private $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function getLadders()
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

    public function getLatestLadders()
    {
        $date = Carbon::now();

        $start = $date->startOfMonth()->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        return \App\LadderHistory::leftJoin("ladders as ladder", "ladder.id", "=", "ladder_history.ladder_id")
            ->where("ladder_history.starts", "=", $start)
            ->where("ladder_history.ends", "=", $end)
            ->whereNotNull("ladder.id")
            ->get();
    }

    public function getPreviousLaddersByGame($cncnetGame, $limit = 5)
    {
        $date = Carbon::now();

        $start = $date->startOfMonth()->subMonth($limit)->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        $ladder = \App\Ladder::where("abbreviation", "=", $cncnetGame)->first();

        if ($ladder === null) return [];

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

        if($ladder == null)
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
            ->orderBy("games.id", "DESC")
            ->paginate(45);
    }

    public function getLadderGameById($history, $gameId)
    {
        if($history == null || $gameId == null)
            return "Invalid parameters";

        return \App\Game::where("id", "=", $gameId)->where('ladder_history_id', $history->id)->first();
    }

    public function getLadderPlayer($history, $username)
    {
        if($history == null)
            return "No ladder found";

        $player = \App\Player::where("ladder_id", "=", $history->ladder->id)
            ->where("username", "=", $username)->first();

        $rank = $player->rank($history);
        $percentile = $player->percentile();

        $playerQuery = $player->playerGames()->where("ladder_history_id", "=", $history->id);

        $points = $playerQuery->sum("points");
        $gamesCount = $playerQuery->count();

        $fpsCount = $playerQuery->where('fps', '>', 25)->count();
        $averageFps = floor($fpsCount ? $playerQuery->where('fps', '>', 25)->sum('fps') / $fpsCount : 0);

        $gamesWon = $player->playerGames()->where("ladder_history_id", "=", $history->id)
                                          ->where('won', true)->count();
        $gamesLost = ($gamesCount - $gamesWon);

        $badge = $player->badge();
        $playerRating = \App\PlayerRating::where("player_id", "=", $player->id)->first()->rating;

        return [
            "id" => $player->id,
            "player" => $player,
            "username" => $player->username,
            "points" => $points,
            "rank" => $rank,
            "game_count" => $gamesCount,
            "games_won" => $gamesWon,
            "games_lost" => $gamesLost,
            "average_fps" => $averageFps,
            "badge" => $badge,
            "rating" => $playerRating,
            "percentile" => $percentile
        ];
    }

    public function getLadderPlayers($date, $cncnetGame, $tier = 1, $paginate, $search)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);

        if ($tier === null)
            $tier = 1;

        if($history == null)
            return [];

        $query = \App\Player::where("ladder_id", "=", $history->ladder->id)
            ->join('player_game_reports as pgr', 'pgr.player_id', '=', 'players.id')
            ->join('game_reports', 'game_reports.id', '=', 'pgr.game_report_id')
            ->join('games', 'games.id', '=', 'game_reports.game_id')
            ->join('player_histories as ph', 'ph.player_id', '=', 'players.id');

        if ($search)
        {
            $query->where('players.username','LIKE',"%{$search}%");
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
                "players.*")
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
}