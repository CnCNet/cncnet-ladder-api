<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClanRating extends Model
{
    public static $DEFAULT_RATING = 1200;

    public function __construct()
    {
        $this->rating = ClanRating::$DEFAULT_RATING;
        $this->peak_rating = 0;
        $this->rated_games = 0;
    }

    public static function createNew($clan, $rating, $ratedGames = 0, $peakRating = 0)
    {
        $userRating = new ClanRating();
        $userRating->rating = $rating;
        $userRating->rated_games = $ratedGames;
        $userRating->peak_rating = $peakRating;
        $userRating->clan_id = $clan->id;
        $userRating->save();
        return $userRating;
    }

    public function clan()
    {
        return $this->hasOne("App\Clan");
    }
}
