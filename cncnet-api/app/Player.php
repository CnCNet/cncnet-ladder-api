<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Player extends Model 
{
	protected $table = 'players';

	protected $fillable = ['id', 'username', 'win_count', 'loss_count', 'games_count', 
    'dc_count', 'oos_count', 'points', 'countries', 'ladder_id'];

    protected $hidden = ['user_id', 'created_at', 'updated_at'];

    public function stats()
	{
        return $this->hasMany('App\GameStats');
	}
        
    public function games()
    {
        return $this->hasMany("App\PlayerGame");
    }

    public function ladder()
    {
        return $this->belongsTo("App\Ladder");
    }
}