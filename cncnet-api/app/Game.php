<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model 
{
    protected $table = 'games';

    protected $fillable = 
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
        'hash'
    ];

    public function stats()
	{
        return $this->hasMany('App\GameStats');
	}

    public function players()
	{
        return $this->hasMany('App\Player');
	}
}