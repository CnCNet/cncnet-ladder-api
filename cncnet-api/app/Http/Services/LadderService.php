<?php namespace App\Http\Services;

use \Illuminate\Database\Eloquent\Collection;

class LadderService 
{
    public function __construct()
    {

    }
    
    public function getLadders()
    {
        return \App\Ladder::all();
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
            ->orderBy("points", "DESC")
            ->limit($limit)
            ->get();

        return $players;
    }

    public function getRecentLadderGames($game, $limit = 4)
    {
        $ladder = $this->getLadderByGame($game);
 
        $recentGames =  \App\Game::where("ladder_id", "=", $ladder->id)
            ->leftJoin('player_points as pp', 'games.id', '=', 'pp.game_id')
            ->whereNotNull('pp.id')
            ->orderBy("games.id", "DESC")
            ->select("games.*")
            ->distinct()
            ->limit(4)
            ->get();

        return $recentGames;
    }

    public function getLadderGameById($game, $gameId)
    {
        $ladder = $this->getLadderByGame($game);
 
        if($ladder == null || $gameId == null)
            return "Invalid parameters";

        $ladderGame = \App\LadderGame::where("ladder_id", "=", $ladder->id)
            ->where("game_id", "=", $gameId)->first();

        if($ladderGame == null)
            return null;

        return \App\Game::where("id", "=", $ladderGame->game_id)->first();
    }

    public function getLadderPlayer($ladder, $player)
    {
        if($ladder == null)
            return "No ladder found";

        $player = \App\Player::where("ladder_id", "=", $ladder->id)
            ->where("username", "=", $player)->first();

        $rank = $this->getLadderPlayerRank($ladder->abbreviation, $player->username);
        $games = \App\PlayerGame::where("player_id", "=", $player->id);
        $gamesCount = $games->count();
        $gamesWon = \App\PlayerGame::where("player_id", "=", $player->id)->where("result", "=", 1)->count();
        $gamesLost = ($gamesCount - $gamesWon);
        $averageFps = $this->calculateAverageFPS($games);
        $badge = $player->badge($player->points);

        return [
            "username" => $player->username, 
            "points" => $player->points, 
            "rank" => $rank, 
            "game_count" => $gamesCount, 
            "games_won" => $gamesWon,
            "games_lost" => $gamesLost,
            "average_fps" => $averageFps,
            "badge" => $badge
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

    public function getLadderPlayers($game)
    {
        $ladder = $this->getLadderByGame($game);

        if($ladder == null)
            return "No ladder found";
        
        $players = new Collection();
        $ladderPlayers = \App\Player::where("ladder_id", "=", $ladder->id)->get();

        foreach($ladderPlayers as $player)
        {
            $player["points"] = $playerPoints = \App\PlayerPoint::where("player_id", "=", $player->id)->sum("points_awarded");
            $players->add($player);
        }

        return $players->sortByDesc('points')->values()->all();
    }

    public function getLadderPlayerRank($game, $username)
    {
        $ladder = $this->getLadderByGame($game);

        if($ladder == null)
            return "No ladder found";

        $players = \App\Player::where("ladder_id", "=", $ladder->id)
            ->orderBy("points", "DESC")
            ->get();

        foreach($players as $k => $player)
        {
            if($player->username == $username)
                return $k + 1;
        }

        return -1;
    }
}
