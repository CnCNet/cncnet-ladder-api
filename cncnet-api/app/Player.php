<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Player extends Model 
{
	protected $table = 'players';

	protected $fillable = ['user_id', 'username', 'win_count', 'loss_count', 'games_count', 
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

    public function rank($game, $username)
    {
        $ladder = \App\Ladder::where("abbreviation", "=", $game)->first();

        if($ladder == null)
            return null;

        $players = \App\Player::where("ladder_id", "=", $ladder->id)
            ->orderBy("points", "DESC")
            ->get();

        foreach($players as $k => $player)
        {
            if($player->username == $username)
                return $k + 1;
        }

        return -1;
    }
}