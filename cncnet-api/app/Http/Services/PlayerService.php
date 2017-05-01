<?php namespace App\Http\Services;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class PlayerService 
{
    public function __construct()
    {

    }

    public function addPlayerToUser($username, $user)
    {
        $player = \App\Player::where("username", "=", $username)->first();
        
        if ($player == null)
        {
            $player = new \App\Player();
            $player->username = $username;
            $player->user_id = $user->id;
            $player->save();

            return $player;
        }
    }

    public function findPlayerById($id)
    {
        $player = \App\Player::find($id);

        if($player != null)
            return $player->first();
        else
            return null;
    }
}
