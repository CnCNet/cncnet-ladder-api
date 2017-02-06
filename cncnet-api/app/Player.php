<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Player extends Model 
{
	protected $table = 'players';

	protected $fillable = ['username', 'win_count', 'loss_count', 'games_count', 
    'dc_count', 'oos_count', 'points', 'countries'];

    protected $hidden = ['user_id', 'created_at', 'updated_at', 'ladder_id'];
}