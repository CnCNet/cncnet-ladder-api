<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapHeader extends Model
{
    protected $table = 'map_headers';
    public $timestamps = false;

    public function waypoints()
    {
        return $this->hasMany(MapWaypoint::class);
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }
}
