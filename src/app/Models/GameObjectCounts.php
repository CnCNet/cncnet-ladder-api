<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameObjectCounts extends Model
{

    //
    public $timestamps = false;

    public function stats()
    {
        return $this->belongsTo('App\Models\Stats2', 'stats_id');
    }

    public function countableGameObject()
    {
        return $this->belongsTo('App\Models\CountableGameObject', 'countable_game_objects_id');
    }
}
