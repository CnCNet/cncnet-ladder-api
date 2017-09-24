<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Collection;

class Player extends Model
{
	protected $table = 'players';

	protected $fillable = ['user_id', 'username', 'win_count', 'loss_count', 'games_count',
    'dc_count', 'oos_count', 'points', 'countries', 'ladder_id'];

    protected $hidden = ['user_id', 'created_at', 'updated_at'];

    public function wins()
    {
        return $this->hasMany("App\PlayerGame")->where("result", "=", 1)->count();
    }

    public function totalGames()
    {
        return $this->hasMany("App\PlayerGame")->count();
    }

    public function stats()
	{
        return $this->hasMany('App\GameStats');
	}

    public function games()
    {
        return $this->hasMany("App\PlayerGame");
    }

    public function rating()
    {
        return $this->hasMany("App\PlayerRating");
    }

    public function ladder()
    {
        return $this->belongsTo("App\Ladder");
    }

    public function card()
    {
        return $this->belongsTo("App\Card");
    }

    public function rank($history, $username)
    {
        $players = new Collection();
        $ladderPlayers = \App\Player::where("ladder_id", "=", $history->ladder->id)->get();

        foreach($ladderPlayers as $player)
        {
            $player["points"] = \App\PlayerPoint::where("player_id", "=", $player->id)
            ->where("ladder_history_id", "=", $history->id)
            ->sum("points_awarded");

            $players->add($player);
        }

        $players = $players->sortByDesc('points')->values()->all();
        foreach($players as $k => $p)
        {
            if ($p->username == $username)
            {
                return $k + 1;
            }
        }
        return -1;
    }

    public function playerPoints($history, $username)
    {
        $player = \App\Player::where("username", "=", $username)
            ->where("ladder_id", "=", $history->ladder->id)->first();

        if ($player == null) return "No player";

        return \App\PlayerPoint::where("player_id", "=", $player->id)
            ->where("ladder_history_id", "=", $history->id)
            ->sum("points_awarded");
    }

    public function badge($points)
    {
        $points = ceil($points);

        if ($points >= 3000)
        {
            return "badge-3000";
        }
        else if ($points >= 2000)
        {
            return "badge-2000";
        }
        else if ($points >= 1500)
        {
            return "badge-1500";
        }
        else if ($points >= 1000)
        {
            return "badge-1000";
        }
        else if ($points >= 500)
        {
            return "badge-500";
        }
        else if ($points >= 250)
        {
            return "badge-250";
        }
        else if ($points >= 100)
        {
            return "badge-100";
        }
        else
        {
            return "";
        }
    }
}