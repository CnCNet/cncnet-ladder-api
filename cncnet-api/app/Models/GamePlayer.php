<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePlayer extends Model
{
	protected $table = 'game_players';
    public $timestamps = false;

    public function player()
	{
        return $this->belongsTo(Player::class);
	}
}