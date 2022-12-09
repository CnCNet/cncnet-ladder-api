<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class MapHeader extends Model
{
    protected $table = 'map_headers';
    public $timestamps = false;

    public function waypoints()
    {
        return $this->hasMany('App\MapWaypoint');
    }

    public function map()
    {
        return $this->belongsTo('App\Map');
    }
}
