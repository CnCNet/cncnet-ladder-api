<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaguePlayer extends Model
{
    protected $table = 'league_players';

    public function getCanPlayBothTiers()
    {
        return $this->can_play_both_tiers == 1;
    }
}
