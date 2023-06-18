<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PlayerGameReport extends Model
{
    // This is a report of disputable information about a game. Undisputed information is stored in the games table
    protected $fillable = [
        'game_id',
        'game_report_id',
        'player_id',
        'clan_id',
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
        'created_at',
        'updated_at'
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

    public function clan()
    {
        return $this->belongsTo("App\Clan");
    }

    public function stats()
    {
        return $this->hasOne("App\Stats2");
    }

    public function wonOrDisco()
    {
        return $this->won || ($this->disconnected && $this->player_id == $this->gameReport->player_id);
    }

    public static function getBestPlayerGameReport($gameId, $clanId, $isMyTeam)
    {
        $playerGameReports = \App\PlayerGameReport::where('game_id', $gameId);

        if ($isMyTeam)
            $playerGameReports = $playerGameReports->where('clan_id', '=', $clanId)->get();
        else
            $playerGameReports = $playerGameReports->where('clan_id', '!=', $clanId)->get();

        $playerGameReport = null;

        //find a winning game report if there was one. It could be one teammate died and marked as lost, but his teammate won
        foreach ($playerGameReports as $p)
        {
            if ($p->won)
            {
                $playerGameReport = $p;
                break;
            }
        }

        if ($playerGameReport == null)
            $playerGameReport = $playerGameReports->first();

        return $playerGameReport;
    }
}
