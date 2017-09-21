<?php namespace App\Http\Services;

use \Illuminate\Database\Eloquent\Collection;
use \Carbon\Carbon;

class LadderService
{
    public function __construct()
    {

    }

    public function getLadders()
    {
        $ladders = \App\Ladder::all();
        foreach ($ladders as $ladder)
        {
            $ladder["sides"] = $ladder->sides()->get();
            $ladder["vetoes"] = $ladder->qmLadderRules()->first()->map_vetoes;
            $ladder["allowed_sides"] = array_map('intval',
                                                 explode(',', $ladder->qmLadderRules()->first()->allowed_sides));
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

    public function getActiveLadderByDate($date, $cncnetGame = null)
    {
        $date = explode("-", $date);
        $month = $date[0];
        $year = $date[1];

        $date = Carbon::create($year, $month, 01, 0);

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
        ->limit(4)
        ->get();
    }

    public function getLadderGameById($history, $gameId)
    {
        if($history == null || $gameId == null)
            return "Invalid parameters";

        $ladderGame = \App\LadderGame::where("ladder_history_id", "=", $history->id)
            ->where("game_id", "=", $gameId)->first();

        if($ladderGame == null)
            return null;

        return \App\Game::where("id", "=", $ladderGame->game_id)->first();
    }

    public function getLadderPlayer($history, $player)
    {
        if($history == null)
            return "No ladder found";

        $player = \App\Player::where("ladder_id", "=", $history->ladder->id)
            ->where("username", "=", $player)->first();

        $rank = $this->getLadderPlayerRank($history, $player->username);
        $points = \App\PlayerPoint::where("player_id", "=", $player->id)->sum("points_awarded");
        $games = \App\PlayerGame::where("player_id", "=", $player->id);
        $gamesCount = $games->count();
        $gamesWon = \App\PlayerGame::where("player_id", "=", $player->id)->where("result", "=", 1)->count();
        $gamesLost = ($gamesCount - $gamesWon);
        $averageFps = $this->calculateAverageFPS($games);
        $badge = $player->badge($points);
        $playerRating = \App\PlayerRating::where("player_id", "=", $player->id)->first()->rating;

        return [
            "id" => $player->id,
            "username" => $player->username,
            "points" => $points,
            "rank" => $rank,
            "game_count" => $gamesCount,
            "games_won" => $gamesWon,
            "games_lost" => $gamesLost,
            "average_fps" => $averageFps,
            "badge" => $badge,
            "rating" => $playerRating
        ];
    }

    private function calculateAverageFPS($games)
    {
        $afps = 0;
        $count = $games->count();
        $games = $games->get();

        foreach($games as $game)
        {
            $g = $game->game()->first();
            if ($g != null)
            {
                $afps += $g->afps;
            }
        }

        if ($count > 0)
        {
            return round($afps / $count);
        }

        return $afps;
    }

    public function getLadderPlayers($date, $cncnetGame)
    {
        $history = $this->getActiveLadderByDate($date, $cncnetGame);

        if($history == null)
            return "No ladder found";

        $players = new Collection();
        $ladderPlayers = \App\Player::where("ladder_id", "=", $history->ladder->id)->get();

        foreach($ladderPlayers as $player)
        {
            $player["points"] = \App\PlayerPoint::where("player_id", "=", $player->id)
                ->where("ladder_history_id", "=", $history->id)
                ->sum("points_awarded");

            $players->add($player);
        }

        return $players->sortByDesc('points')->values()->all();
    }

    public function getLadderPlayerRank($historyId, $username)
    {
        $player = new \App\Player();
        return $player->rank($historyId, $username);
    }
}
