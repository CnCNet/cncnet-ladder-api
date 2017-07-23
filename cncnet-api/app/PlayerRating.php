<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerRating extends Model 
{
    public function __construct()
    {
        $this->rating = 1200;
        $this->peak_rating = 0;
        $this->rated_games = 0;
    }
}