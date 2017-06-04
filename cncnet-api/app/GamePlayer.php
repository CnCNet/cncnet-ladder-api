<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class GamePlayer extends Model
{
	protected $table = 'game_players';
    public $timestamps = false;

    public function player()
	{
        return $this->belongsTo('App\Player');
	}
}