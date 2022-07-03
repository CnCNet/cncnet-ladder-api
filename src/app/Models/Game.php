<?php

namespace App\Models;

use App\Models\QmMatch;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'games';

    protected $fillable =
    [
        'ladder_id',
        'wol_game_id',
        'game_report_id',
        'bamr',
        'crat',
        'cred',
        'shrt',
        'supr',
        'unit',
        'plrs',
        'scen',
        'hash'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public static $gameColumns =
    [
        'bamr',
        'crat',
        'cred',
        'shrt',
        'supr',
        'unit',
        'plrs',
        'scen',
        'hash'
    ];

    public function map()
    {
        return $this->belongsTo('App\Models\Map', 'hash', 'hash');
    }

    public function allReports()
    {
        return $this->hasMany('App\Models\GameReport');
    }

    public function report()
    {
        return $this->belongsTo('App\Models\GameReport', 'game_report_id');
    }

    public function playerGameReports()
    {
        return $this->hasMany('App\Models\PlayerGameReport')->where('game_report_id', $this->game_report_id, 'game_report_id');
    }
    public function ladderHistory()
    {
        return $this->belongsTo('App\Models\LadderHistory');
    }

    public static function genQmEntry(QmMatch $qmMatch)
    {
        $game = new Game;
        $game->ladder_history_id = $qmMatch->ladder->currentHistory()->id;
        foreach (Game::$gameColumns as $col)
        {
            $game[$col] = 0;
        }
        $game->wol_game_id = 0;
        $game->hash = $qmMatch->map->hash;
        $game->game_report_id = null;
        $game->save();
        return $game;
    }

    public function qmMatch()
    {
        return $this->belongsTo('\App\Models\QmMatch');
    }
}
