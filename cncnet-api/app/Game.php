<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model 
{
    protected $table = 'games';

    protected $fillable = 
    [
        'ladder_id',
        'wol_game_id',
        'afps',
        'oosy',
        'bamr',
        'crat',
        'dura',
        'cred',
        'shrt',
        'supr',
        'unit',
        'plrs',
        'scen',
        'sdfx',
        'hash'
    ];

    protected $hidden = ['created_at', 'updated_at'];
        
    public $gameColumns = 
    [
        'afps',
        'oosy',
        'bamr',
        'crat',
        'dura',
        'cred',
        'shrt',
        'supr',
        'unit',
        'plrs',
        'scen',
        'sdfx',
        'hash'
    ];

    public function stats()
	{
        return $this->hasMany('App\GameStats');
	}

    public function players()
	{
        return $this->hasMany('App\PlayerGame');
	}

    public function map()
    {
        return $this->belongsTo('App\Map', 'hash');
    }

    public function playerPoints()
    {
        return $this->hasMany('App\PlayerPoint', 'game_id');
    }
}