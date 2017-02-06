<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class LadderType extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'ladder_types';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['name', 'type', 'match'];
}
