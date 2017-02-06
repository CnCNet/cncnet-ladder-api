<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class GameStats extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'game_stats';

	/**
	 * The attributes that are mass assignable. 
	 *
	 * @var array
	 */
	protected $fillable = ['cmp', 'col', 'sid', 'pc', 
    'hrv', 'crd', 'inb', 'unb', 'plb', 'blb', 'vsb', 
    'inl', 'unl', 'pll', 'bll', 'vsl', 'ink', 'unk', 
    'plk', 'blk', 'vsk', 'blc', 'spc', 'ded', 'spa', 
    'rsg', 'aly', 'tid', 'ipa'];
}