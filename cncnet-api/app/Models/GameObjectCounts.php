<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameObjectCounts extends Model {

	//
    public $timestamps = false;

    public function stats()
    {
        return $this->belongsTo(Stats2::class, 'stats_id');
    }

    public function countableGameObject()
    {
        return $this->belongsTo(CountableGameObject::class, 'countable_game_objects_id');
    }
}
