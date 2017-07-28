<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class QmMap extends Model {

	//
    public function qmMatches()
    {
        return $this->hasMany('App\QmMatch');
    }

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }

    public function map()
    {
        return $this->belongsTo('App\Map');
    }
}
