<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'games';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['wol_gid', 'duration', 'afps', 'crates', 'oosy', 'bases', 'units', 'tech'];

    /**
	* The attributes excluded from the model's JSON form.
	*
	* @var array
	*/
    protected $hidden = ['created_at', 'updated_at'];
}