<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{
    const GAME_TYPE_1VS1 = 0;
    const GAME_TYPE_1VS1_AI = 1;
    const GAME_TYPE_2VS2_AI = 2;
    const GAME_TYPE_2VS2 = 3;

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

    public function map()
    {
        return $this->belongsTo(Map::class, 'hash', 'hash');
    }

    public function allReports()
    {
        return $this->hasMany(GameReport::class);
    }

    public function report()
    {
        return $this->belongsTo(GameReport::class, 'game_report_id');
    }

    public function playerGameReports()
    {
        return $this->hasMany(PlayerGameReport::class)->where('game_report_id', $this->game_report_id, 'game_report_id');
    }

    public function player_game_reports()
    {
        return $this->hasManyThrough(PlayerGameReport::class, GameReport::class);
    }

    public function ladderHistory()
    {
        return $this->belongsTo(LadderHistory::class);
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

    public function gameType()
    {
        return $this->game_type;
    }

    public function qmMatch()
    {
        return $this->belongsTo(QmMatch::class);
    }

    public function gameClips()
    {
        return $this->hasMany(GameClip::class, 'game_id');
    }

    public function observers()
    {
        return $this->hasMany(PlayerGameReport::class, 'game_report_id', 'game_report_id')
            ->where('spectator', true)
            ->with(['player', 'user']);
    }


    public function players()
    {
        return $this->hasMany(PlayerGameReport::class, 'game_report_id', 'game_report_id')
            ->where('spectator', false)
            ->with('player');
    }
}
