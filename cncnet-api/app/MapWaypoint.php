<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class MapWaypoint extends Model
{
    protected $table = 'map_waypoints';
    public $timestamps = false;

    public function mapHeader()
    {
        return $this->belongsTo('App\MapHeader');
    }
}
