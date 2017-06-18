<?php namespace App\Http\Services;

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

    public function getLadderPlayer($game, $player)
    {
        $ladder = $this->getLadderByGame($game);

        if($ladder == null)
            return "No ladder found";

        return \App\Player::where("ladder_id", "=", $ladder->id)
            ->where("username", "=", $player)->first();
    }

    public function getLadderPlayers($game)
    {
        $ladder = $this->getLadderByGame($game);

        if($ladder == null)
            return "No ladder found";

        return \App\Player::where("ladder_id", "=", $ladder->id)
            ->where("games_count", ">", "0")
            ->orderBy("points", "DESC")
            ->get();
    }

    public function getLadderPlayerRank($game, $username)
    {
        $ladder = $this->getLadderByGame($game);

        if($ladder == null)
            return "No ladder found";

        $players = \App\Player::where("ladder_id", "=", $ladder->id)
            ->where("games_count", ">", "0")
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
