<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountableGameObject extends Model
{

    //
    protected $fillable = ['heap_name', 'heap_id', 'name', 'cameo', 'cost', 'value', 'ui_name', 'game_object_schema_id'];

    /*
    public function fill($data)
    {
        foreach ($fillable as $attribute)
        {
            $this->$attribute = $data[$attribute];
        }
    }*/

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }

    public function gameObjectCounts()
    {
        return $this->hasMany('App\GameObjectCounts');
    }
}
