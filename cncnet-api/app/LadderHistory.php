<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class LadderHistory extends Model
{
	protected $table = 'ladder_history';
    public $timestamps = false;

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }

    public function games()
    {
        return $this->hasMany('App\Games');
    }
}
