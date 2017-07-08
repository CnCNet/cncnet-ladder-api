<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Stats extends Model
{
	protected $table = 'stats';

	protected $fillable = [
        'sid', 'col', 'cty',
        'crd',  'crd', 'unl', 'inl', 'pll',
        'bll', 'unb', 'inb', 'plb', 'blb', 'unk', 'ink',
        'plk', 'blk', 'blc', 'cra', 'hrv'
    ];

	public $gameStatsColumns = [
        'sid', 'col', 'cty',
        'crd',  'crd', 'unl', 'inl', 'pll',
        'bll', 'unb', 'inb', 'plb', 'blb', 'unk', 'ink',
        'plk', 'blk', 'blc', 'cra', 'hrv'
    ];
    
    public $timestamps = false;
}