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

    public function getPreviousLaddersByGame($cncnetGame, $limit = 5)
    {
        $date = Carbon::now();

        $start = $date->startOfMonth()->subMonth($limit)->toDateTimeString();
        $end = $date->endOfMonth()->toDateTimeString();

        $ladder = \App\Ladder::where("abbreviation", "=", $cncnetGame)->first();

        return \App\LadderHistory::where("ladder_history.starts", ">=", $start)
            ->where("ladder_history.ladder_id", "=", $ladder->id)
            ->limit($limit)
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

        return \App\Game::where("id", "=", $gameId)->where('ladder_history_id', $history->id)->first();
    }

    public function getLadderPlayer($history, $player)
    {
        if($history == null)
            return "No ladder found";

        $player = \App\Player::where("ladder_id", "=", $history->ladder->id)
            ->where("username", "=", $player)->first();

        $rank = $player->rank($history);

        $playerQuery = $player->playerGames()->where("ladder_history_id", "=", $history->id);

        $points = $playerQuery->sum("points");
        $gamesCount = $playerQuery->count();
        $gamesWon = $playerQuery->where('won', true)->count();
        $gamesLost = ($gamesCount - $gamesWon);

        $fpsCount = $playerQuery->where('fps', '>', 25)->count();
        $averageFps = floor($fpsCount ? $playerQuery->where('fps', '>', 25)->sum('fps') / $fpsCount : 0);

        $badge = $player->badge($points);
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
            "rating" => $playerRating
        ];
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
            $player["points"] = $player->playerGames()->where('ladder_history_id', $history->id)->sum('points');
            $players->add($player);
        }

        return $players->sortByDesc('points')->values()->all();
    }
}
