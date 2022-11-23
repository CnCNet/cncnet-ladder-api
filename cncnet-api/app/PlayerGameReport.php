<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerGameReport extends Model
{

    // This is a report of disputable information about a game. Undisputed information is stored in the games table
    protected $fillable = [
        'game_id',
        'game_report_id',
        'player_id',
        'points',
        'stats_id',
        'local_id',
        'local_team_id', // Local team id should be the id of the lowest player you are allied with or yourself if you are the lowest id
        'disconnected',
        'no_completion',
        'quit',
        'won',
        'draw',
        'defeated',
        'spectator',
        'created_at'
    ];

    public function player()
    {
        return $this->belongsTo("App\Player");
    }

    public function game()
    {
        return $this->belongsTo("App\Game");
    }

    public function gameReport()
    {
        return $this->belongsTo("App\GameReport");
    }

    public function stats()
    {
        return $this->hasOne("App\Stats2");
    }

    public function wonOrDisco()
    {
        return $this->won || ($this->disconnected && $this->player_id == $this->gameReport->player_id);
    }
}
