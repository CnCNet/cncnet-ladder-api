<?php namespace App;

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

    public $gameColumns =
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

    public function stats()
	{
        return $this->hasMany('App\GameStats');
	}

    public function map()
    {
        return $this->belongsTo('App\Map', 'hash');
    }

    public function gameReports()
    {
        return $this->hasMany('App\GameReport');
    }

    public function playerGameReports()
    {
        return $this->hasMany('App\PlayerGameReport')->where('game_report_id', $this->game_report_id, 'game_report_id');
    }
}