<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Player extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'players';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['username', 'win_count', 'loss_count', 'games_count', 
    'dc_count', 'oos_count', 'points', 'countries'];

    protected $hidden = ['user_id', 'created_at', 'updated_at', 'ladder_id'];
}