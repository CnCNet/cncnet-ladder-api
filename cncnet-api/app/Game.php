<?php

namespace App;

use App\QmMatch;

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
        'hash',
        'game_type'
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

    public static $GAME_TYPE_1VS1 = 0;
    public static $GAME_TYPE_1VS1_AI = 1;
    public static $GAME_TYPE_2VS2_AI = 2;

    public function map()
    {
        return $this->belongsTo('App\Map', 'hash', 'hash');
    }

    public function allReports()
    {
        return $this->hasMany('App\GameReport');
    }

    public function report()
    {
        return $this->belongsTo('App\GameReport', 'game_report_id');
    }

    public function playerGameReports()
    {
        return $this->hasMany('App\PlayerGameReport')->where('game_report_id', $this->game_report_id, 'game_report_id');
    }

    public function ladderHistory()
    {
        return $this->belongsTo('App\LadderHistory');
    }

    public static function genQmEntry(QmMatch $qmMatch, $gameType)
    {
        $game = new Game;
        $game->ladder_history_id = $qmMatch->ladder->currentHistory()->id;
        foreach (Game::$gameColumns as $col)
        {
            $game[$col] = 0;
        }
        $game->hash = $qmMatch->map->hash;
        $game->game_report_id = null;
        $game->game_type = $gameType;
        $game->save();
        return $game;
    }

    public function qmMatch()
    {
        return $this->belongsTo('\App\QmMatch');
    }
}
