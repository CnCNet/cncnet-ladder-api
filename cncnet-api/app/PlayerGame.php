<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerGame extends Model 
{
    protected $table = 'player_games';

    public function player()
    {
        return $this->belongsTo("App\Player");
    }
}