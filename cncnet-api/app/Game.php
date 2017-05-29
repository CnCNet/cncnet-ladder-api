<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model 
{
    protected $table = 'games';

    protected $fillable = ['wol_game_id', 'duration', 'afps', 
    'oosy', 'bamr', 'crat', 'dura', 'cred', 'shrt', 'supr', 'unit', 'plrs', 'scen'];

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