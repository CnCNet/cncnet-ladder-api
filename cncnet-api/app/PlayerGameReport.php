<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerGameReport extends Model {

	// This is a report of disputable information about a game. Undisputed information is stored in the games table
    protected $fillable = ['game_id',
                           'game_report_id',
                           'player_id',
                           'points',
                           'stats',
                           'local_id',
                           'local_team_id', // Local team id should be the id of the lowest player you are allied with or yourself if you are the lowest id
                           'disconnected',
                           'no_completion',
                           'quit',
                           'won',
                           'draw',
                           'defeated',
                           'spectator',
                           'created_at' ];

    public function player()
    {
        return $this->belongsTo("App\Player");
    }

    public function game()
    {
        return $this->belongsTo("App\Game");
    }

    public function stats()
    {
        return $this->belongsTo("App\Stats");
    }
}
