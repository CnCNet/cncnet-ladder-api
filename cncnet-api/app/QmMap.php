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

    public function scopeValid($query)
    {
        return $query->where('valid', true);
    }

    public static function findMapsByLadder($id)
    {
        $ladder = \App\Ladder::find($id);
        $qmMaps = $ladder->mapPool->maps;
        return $qmMaps->map( function($qmMap)
        {
            $qmMap["hash"] = $qmMap->map->hash;
            $qmMap["allowed_sides"] = array_map('intval', explode(',', $qmMap->allowed_sides));
            return $qmMap;
        });
    }

    protected $_map_side_array = null;
    public function sides_array()
    {
        if ($this->_map_side_array === null)
        {
            $this->_map_side_array = explode(',', $this->allowed_sides);
        }
        return $this->_map_side_array;
    }

    public function spawnOptionValues()
    {
        return $this->hasMany('App\SpawnOptionValue');
    }
}
