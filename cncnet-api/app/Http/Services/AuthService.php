<?php namespace App\Http\Services;

class AuthService 
{
    public function __construct()
    {

    }

    public function addUser($request, $user = null)
    {
        if ($user == null)
        {
            $user = new \App\User();
            $user->name = "";
            $user->email = $request->email;
            $user->password = \Hash::make($request->password);
            $user->save();
        }

        return $user;
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

        return null;
    }
}
