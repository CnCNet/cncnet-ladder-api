<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class PlayerRating extends Model
{
    public function __construct()
    {
        $this->rating = 1200;
        $this->peak_rating = 0;
        $this->rated_games = 0;
        $this->tier = 1;
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }

    public function doTierStuff($history)
    {
        $gameCount = $this->player->playerGameReports()->count();
        // If it's the first game of the month  or the 20th game we'll do ladder tier placement.
        if ($gameCount == 1 || $gameCount == 20)
        {
            if ($this->rating > $history->ladder->qmLadderRules->tier2_rating)
                $this->tier = 1;
            else
                $this->tier = 2;
            $this->save();
        }
    }
}