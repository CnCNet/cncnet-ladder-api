<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PlayerRating extends Model
{
    public static $DEFAULT_RATING = 1200;

    public function __construct()
    {
        parent::__construct();
        // NOTE: Phasing out PlayerRating in favour of UserRating

        $this->rating = PlayerRating::$DEFAULT_RATING;
        $this->peak_rating = 0;
        $this->rated_games = 0;
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
