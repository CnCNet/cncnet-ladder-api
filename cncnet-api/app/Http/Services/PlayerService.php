<?php namespace App\Http\Services;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class PlayerService 
{
    public function __construct()
    {

    }

    public function addPlayerToUser($username, $user, $ladderId)
    {
        $player = \App\Player::where("username", "=", $username)
            ->where("ladder_id", "=", $ladderId)->first();
        
        if ($player == null)
        {
            $player = new \App\Player();
            $player->username = $username;
            $player->user_id = $user->id;
            $player->ladder_id = $ladderId;
            $player->save();

            return $player;
        }

        return null;
    }

    public function createPlayerGame($player, $opponent, $gameId, $won)
    {
        $playerGame = \App\PlayerGame::where("player_id", "=", $player->id)
            ->where("game_id", "=", $gameId)->first();

        if ($playerGame == null)
        {
            $playerGame = new \App\PlayerGame();
            $playerGame->game_id = $gameId;
            $playerGame->player_id = $player->id;
            $playerGame->opponent_id = $opponent->id;
            $playerGame->result = $won;
            $playerGame->save();
        }

        return $playerGame;
    }

    public function findPlayerById($id)
    {
        return \App\Player::find($id);
    }

    public function findPlayerByUsername($name, $ladder)
    {
        return \App\Player::where("username", "=", $name)
            ->where("ladder_id", "=", $ladder->id)->first();
    }

    public function awardPlayerPoints($playerId, $gameId, $points, $won = false)
    {
        $playerPoints = new \App\PlayerPoint();
        $playerPoints->player_id = $playerId;
        $playerPoints->game_id = $gameId;
        $playerPoints->points_awarded = $points;
        $playerPoints->game_won = $won;
        $playerPoints->save();
    }

    public function updatePlayerStats($player, $points, $won = false)
    {
        $player->points = $points;
        $player->games_count += 1;

        if ($won)
        {
            $player->win_count += 1;
            $player->points += $points;
        }
        else
        {
            $player->loss_count = $player->loss_count > 0 ? $player->loss_count -= 1 : 0;
            $player->points = $player->points > 0 ? $player->points -= $points : 0;
        }
  
        $player->save();
    }
}
