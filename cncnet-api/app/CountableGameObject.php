<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class CountableGameObject extends Model {

	//
    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }

    public function gameObjectCounts()
    {
        return $this->hasMany('App\GameObjectCounts');
    }
}
