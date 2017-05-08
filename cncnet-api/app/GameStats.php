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
        'rsg', 'aly', 'tid', 'ipa'
    ];

    public $columns = [
        'cmp', 'col', 'sid', 'pc', 'scen',
        'hrv', 'crd', 'inb', 'unb', 'plb', 'blb', 'vsb', 
        'inl', 'unl', 'pll', 'bll', 'vsl', 'ink', 'unk', 
        'plk', 'blk', 'vsk', 'blc', 'spc', 'ded', 'spa', 
        'rsg', 'aly', 'tid', 'ipa'
    ];

    public $timestamps = false;

    public function player()
	{
        return $this->belongsTo('App\Player');
	}
}