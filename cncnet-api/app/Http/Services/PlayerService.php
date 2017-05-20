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
        $player = \App\Player::where("username", "=", $username)->first();
        
        if ($player == null)
        {
            $player = new \App\Player();
            $player->username = $username;
            $player->user_id = $user->id;
            $player->ladder_id = $ladderId;
            $player->save();

            return $player;
        }
    }

    public function createPlayerGame($player, $game)
    {
        $playerGame = \App\PlayerGame::where("player_id", "=", $player->id)
            ->where("game_id", "=", $game->id)->first();

        if ($playerGame == null)
        {
            $playerGame = new \App\PlayerGame();
            $playerGame->game_id = $game->id;
            $playerGame->player_id = $player->id;
            $playerGame->save();
        }

        return $playerGame;
    }

    public function findPlayerById($id)
    {
        return \App\Player::find($id);
    }
}
