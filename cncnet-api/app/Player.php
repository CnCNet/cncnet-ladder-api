<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Collection;

class Player extends Model
{
	protected $table = 'players';

	protected $fillable = ['user_id', 'username', 'win_count', 'loss_count', 'games_count',
    'dc_count', 'oos_count', 'points', 'countries', 'ladder_id'];

    protected $hidden = ['user_id', 'created_at', 'updated_at'];

    public function playerGameReports()
    {
        return $this->hasMany('App\PlayerGameReport');
    }

    public function wins($history = null)
    {
        $result = 0;

        if ($history == null)
        {
            $result = $this->playerGames()->where('won', true)->count();
        }
        else
        {
            $result = $this->playerGames()->where('won', true)->where('ladder_history_id', $history->id)->count();
        }
        return $result;
    }

    public function gameReports()
    {
        return $this->hasMany('App\GameReport')
            ->join('games as g', 'g.id', '=', 'game_reports.game_id')
            ->where('g.game_report_id', '=', 'game_reports.id');
    }

    public function playerGames()
    {
        return $this->playerGameReports()
            ->join('game_reports', 'game_reports.id', '=', 'player_game_reports.game_report_id')
            ->join('games', 'games.id', '=', 'game_reports.game_id')
            ->where('player_game_reports.player_id', $this->id)
            ->where('game_reports.valid', true)
            ->where('game_reports.best_report', true)
            ->select('player_game_reports.id as id', 'game_reports.player_id as player_id',
                     'game_reports.id as game_report_id', 'games.ladder_history_id as ladder_history_id',
                     'game_reports.game_id as game_id', 'duration', 'fps', 'oos',
                     'local_id', 'local_team_id', 'points', 'stats_id', 'disconnected', 'no_completion', 'quit',
                     'won', 'defeated', 'draw', 'spectator', 'game_reports.created_at', 'wol_game_id', 'bamr',
                     'crat', 'cred', 'shrt', 'supr', 'supr', 'unit', 'plrs', 'scen', 'hash');
    }
    public function totalGames($history = null)
    {
        $result = 0;

        if ($history == null)
        {
            $result = $this->playerGames()->count();
        }
        else
        {
            $result = $this->playerGames()->where('ladder_history_id', $history->id)->count();
        }
        return $result;
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
        $player = \App\Player::where("ladder_id", "=", $history->ladder->id)
                             ->where('username', $username)->first();

        $playerPoints = \App\Game::where("ladder_history_id", "=", $history->id)
               ->join('player_game_reports as pgr', 'games.game_report_id', '=', 'pgr.game_report_id')
               ->groupBy('pgr.player_id')
               ->orderBy('points', 'ASC')
               ->selectRaw('pgr.player_id, SUM(points) as points')->get();

        for ($i = 0; $i < $playerPoints->count(); ++$i)
        {
            if ($playerPoints[$i]->player_id = $player->id)
                return $i + 1;
        }
        return -1;
    }

    public function playerPoints($history, $username)
    {
        $player = \App\Player::where("username", "=", $username)
            ->where("ladder_id", "=", $history->ladder->id)->first();

        if ($player == null) return "No player";

        return $this->playerGames()->sum("points");
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