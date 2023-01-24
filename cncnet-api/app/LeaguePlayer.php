<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaguePlayer extends Model
{
    protected $table = 'league_players';

    public static function playerCanPlayBothTiers($user, $ladder)
    {
        $canPlayBoth = false;

        $leaguePlayer = LeaguePlayer::where("user_id", $user->id)->where("ladder_id", $ladder->id)->first();
        if ($leaguePlayer && $leaguePlayer->getCanPlayBothTiers() === true)
        {
            $canPlayBoth = true;
        }

        return $canPlayBoth;
    }

    public function getCanPlayBothTiers()
    {
        return $this->can_play_both_tiers == 1;
    }
}
