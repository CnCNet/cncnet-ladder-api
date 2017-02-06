<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Ladder extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'ladders';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['name', 'abbreviation'];
}
