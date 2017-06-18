<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class GameStats extends Model
{
	protected $table = 'game_stats';

	protected $fillable = [
        'cmp', 'col', 'sid',
        'spc',  'ipa', 'bamr', 'oosy', 'afps',
        'plrs', 'unit', 'mode', 'supr', 'shrt', 'cred', 'dura',
        'fini', 'trny', 'gsku', 'idno', 'vers'
    ];

    public $playerStatsColumns = [
        'cmp', 'col', 'sid',
        'ipa', 'bamr'     
    ];
    
    public $gameStatsColumns = 
    [
        'vers',
        'afps',
        'plrs',
        'fini',
        'oosy',
        'trny',
        'gsku',
        'idno'
    ];
    
    public $timestamps = false;

    public function player()
	{
        return $this->belongsTo('App\Player');
	}

    public function game()
    {
        return $this->belongsTo('App\Game');
    }
}