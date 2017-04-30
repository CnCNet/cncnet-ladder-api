<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model 
{
    protected $table = 'games';

    protected $fillable = ['wol_game_id', 'duration', 'afps', 'crates', 'oosy', 'bases', 'units', 'tech'];

    protected $hidden = ['created_at', 'updated_at'];

    public function stats()
	{
        return $this->hasMany('App\GameStats');
	}

    public function players()
	{
        return $this->hasMany('App\Player');
	}
}