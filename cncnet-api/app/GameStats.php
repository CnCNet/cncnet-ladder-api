<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class GameStats extends Model
{
	protected $table = 'game_stats';

	protected $fillable = [
        'cmp', 'col', 'sid', 'pc', 'scen',
        'hrv', 'crd', 'inb', 'unb', 'plb', 'blb', 'vsb', 
        'inl', 'unl', 'pll', 'bll', 'vsl', 'ink', 'unk', 
        'plk', 'blk', 'vsk', 'blc', 'spc', 'ded', 'spa', 
        'rsg', 'aly', 'tid', 'ipa', 'bamr', 'oosy', 'afps',
        'plrs', 'unit', 'mode', 'supr', 'shrt', 'cred', 'dura',
        'fini', 'trny', 'gsku', 'idno', 'vers'
    ];

    public $playerStatsColumns = [
        'cmp', 'col', 'sid', 'pc', 'scen',
        'hrv', 'crd', 'inb', 'unb', 'plb', 'blb', 'vsb', 
        'inl', 'unl', 'pll', 'bll', 'vsl', 'ink', 'unk', 
        'plk', 'blk', 'vsk', 'blc', 'spc', 'ded', 'spa', 
        'rsg', 'aly', 'tid', 'ipa', 'bamr'     
    ];
    
    public $gameStatsColumns = 
    [
        //'myid',
        //'acco',
        //'date',
        'vers',
        //'sped',
        'afps',
        //'time',
        //'spid',
        'plrs',
        //'pngr',
        //'pngs',
        'scen',
        'unit',
        //'aipl',
        'crat',
        //'base',
        'bamr',
        'mode',
        'supr',
        'shrt',
        'cred',
        'dura',
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
}