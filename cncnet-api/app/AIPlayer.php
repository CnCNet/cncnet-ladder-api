<?php

namespace App;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AIPlayer
{
    /**
     * To be called by seeder
     * @return void 
     */
    public static function createOrGetAIBot($username, $ladderId)
    {
        $userEmail = "aiplayerbot@cncnet.org";
        $user = User::where("email", $userEmail)->first();

        if ($user == null)
        {
            $user = new User();
            $user->name = "AIBot";
            $user->email = $userEmail;
            $user->email_verified = 1;
            $user->password = Hash::make(Str::random(18));
            $user->save();

            $userSettings = new \App\UserSettings();
            $userSettings->user_id = $user->id;
            $userSettings->save();
        }

        $username = str_replace([",", ";", "="], "-", $username); // Dissallowed by qm client

        $player = Player::where("username", "=", $username)
            ->where("ladder_id", "=", $ladderId)
            ->first();

        $ladder = Ladder::find($ladderId);

        if ($player == null)
        {
            $player = new Player();
            $player->username = $username;
            $player->user_id = $user->id;
            $player->ladder_id = $ladder->id;
            $player->is_bot = true;
            $player->save();

            PlayerActiveHandle::setPlayerActiveHandle($ladder->id, $player->id, $user->id);
            UserRating::createNewFromLegacyPlayerRating($user);
        }

        return $player;
    }

    public static function getAIPlayer($history)
    {
        $ladder = $history->ladder;
        $player = AIPlayer::createOrGetAIBot("BrutalBot", $ladder->id);

        return $player;
    }
}
