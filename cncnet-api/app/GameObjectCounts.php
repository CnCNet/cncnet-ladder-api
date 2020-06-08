<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class GameObjectCounts extends Model {

	//
    public $timestamps = false;

    public function stats()
    {
        return $this->belongsTo('App\Stats2', 'stats_id');
    }

    public function countableGameObject()
    {
        return $this->belongsTo('App\CountableGameObject', 'countable_game_objects_id');
    }
}
