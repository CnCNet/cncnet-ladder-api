<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Collection;
use Log;
use \Carbon\Carbon;

class Player extends Model
{
	protected $table = 'players';

	protected $fillable = ['user_id', 'username', 'win_count', 'loss_count', 'games_count',
    'dc_count', 'oos_count', 'points', 'countries', 'ladder_id'];

    protected $hidden = ['user_id', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo("\App\User");
    }

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
            ->select('player_game_reports.id as id', 'player_game_reports.player_id as player_id',
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
        return $this->hasOne("App\PlayerRating");
    }

    public function percentile()
    {
        if ($this->rating->rated_games < 10)
            return 0;

        $playerRatings = \App\PlayerRating::join('players as p', 'p.id', '=', 'player_id')
                            ->where("ladder_id", "=", $this->ladder_id)
                            ->where('rated_games', '>', 10)
                            ->where('player_ratings.updated_at', '>', Carbon::now()->subMonths(2))
                            ->orderBy('rating', 'ASC');

        $ratingsCount = $playerRatings->count();
        $count = 0;
        foreach ($playerRatings->get() as $playerRating)
        {
            if($playerRating->player_id == $this->id)
            {
                break;
            }
            $count++;
        }
        $ptile = (($count/$ratingsCount) * 100) - 1;

        return $ptile >= 0 ? $ptile : 0;
    }

    public function ladder()
    {
        return $this->belongsTo("App\Ladder");
    }

    public function card()
    {
        return $this->belongsTo("App\Card");
    }

    public function rank($history)
    {
        $ppQuery = \App\PlayerHistory::join('player_game_reports as pgr', 'pgr.player_id', '=', 'player_histories.player_id')
                                      ->join('games', 'pgr.game_report_id', '=', 'games.game_report_id')
                                      ->where('games.ladder_history_id', '=', $history->id)
                                      ->where("player_histories.ladder_history_id", '=', $history->id)
                                      ->where('player_histories.tier', '=', $this->playerHistory($history)->tier)
                                      ->groupBy('pgr.player_id')
                                      ->orderBy('points', 'DESC')
                                      ->selectRaw('pgr.player_id, SUM(points) as points');

        $playerPoints = $ppQuery->get();

        for ($i = 0; $i < $playerPoints->count(); ++$i)
        {
            if ($playerPoints[$i]->player_id == $this->id)
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

    public function points($history)
    {
        $points = \App\PlayerGameReport::where('player_game_reports.player_id', $this->id)
                    ->join('games as g', 'g.game_report_id', '=', 'player_game_reports.game_report_id')
                    ->where("g.ladder_history_id", "=", $history->id)
                    ->sum('player_game_reports.points');
        return $points !== null ? $points : 0;
    }

    public function pointsBefore($history, $game_id)
    {
        $points = \App\PlayerGameReport::where('player_game_reports.player_id', $this->id)
                ->join('games as g', 'g.game_report_id', '=', 'player_game_reports.game_report_id')
                ->where("g.ladder_history_id", "=", $history->id)
                ->where('g.id', '<', $game_id)
                ->sum('player_game_reports.points');
        return $points !== null ? $points : 0;
    }

    public function badge($percentile = null)
    {
        if ($percentile == null)
        {
            $percentile = $this->percentile();
        }

        if ($percentile > 0 && $percentile <= 15)
        {
            return ["badge" => "badge-t1", "type" => "Officer Cadet"];
        }
        else if ($percentile > 15 && $percentile <= 25)
        {
            return ["badge" => "badge-t2", "type" => "Second Lieutenant"];
        }
        else if ($percentile > 25 && $percentile <= 45)
        {
            return ["badge" => "badge-t3", "type" => "Lieutenant"];
        }
        else if ($percentile > 45 && $percentile <= 55)
        {
            return ["badge" => "badge-t4", "type" => "Captain"];
        }
        else if ($percentile > 55 && $percentile <= 65)
        {
            return ["badge" => "badge-t5", "type" => "Major"];
        }
        else if ($percentile > 65 && $percentile <= 75)
        {
            return ["badge" => "badge-t6", "type" => "Lieutenant Colonel"];
        }
        else if ($percentile > 75 && $percentile <= 85)
        {
            return ["badge" => "badge-t7", "type" => "Colonel"];
        }
        else if ($percentile > 85 && $percentile <= 90)
        {
            return ["badge" => "badge-t8", "type" => "Brigadier"];
        }
        else if ($percentile > 90 && $percentile <= 100)
        {
            return ["badge" => "badge-t9", "type" => "Major General"];
        }
        else
        {
            return ["badge" => "badge-default", "type" => "Recruit"];
        }
    }

    public function playerRating()
    {
        return $this->hasOne("App\PlayerRating");
    }

    public function playerHistories()
    {
        return $this->hasMany("App\PlayerHistory");
    }

    public function playerHistory($history)
    {
        $pHist = $this->playerHistories()->where('ladder_history_id', '=', $history->id)->get()->first();
        if ($pHist === null)
        {
            $this->doTierStuff($history);
        }
        return $this->playerHistories()->where('ladder_history_id', '=', $history->id)->get()->first();
    }

    public function doTierStuff($history)
    {
        $gameCount = $this->playerGameReports()->count();
        $pHist = $this->playerHistories()->where('ladder_history_id', '=', $history->id)->get()->first();

        if ($pHist === null)
        {
            $pHist = new \App\PlayerHistory;
            $pHist->ladder_history_id = $history->id;
            $pHist->player_id = $this->id;

            $pHist->tier = 1;
            if ($this->playerRating->rating <= $history->ladder->qmLadderRules->tier2_rating)
            {
                $pHist->tier = 2;
            }
            $pHist->save();
        }
        // If it's the first game of the month  or the 20th game we'll do ladder tier placement.
        else if ($gameCount == 20)
        {
            if ($this->rating->rating > $history->ladder->qmLadderRules->tier2_rating)
                $pHist->tier = 1;
            else
                $pHist->tier = 2;
            $pHist->save();
        }
    }
}