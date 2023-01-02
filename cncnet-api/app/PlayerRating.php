<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class PlayerRating extends Model
{
    public static $DEFAULT_RATING = 1200;

    public function __construct()
    {
        // NOTE: Phasing out PlayerRating in favour of UserRating

        $this->rating = PlayerRating::$DEFAULT_RATING;
        $this->peak_rating = 0;
        $this->rated_games = 0;
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }
}
