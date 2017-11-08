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

    public static function findMapsByLadder($id)
    {
        $qmMaps = \App\QmMap::where('ladder_id', $id)->get();

        return $qmMaps->map( function($qmMap)
        {
            $qmMap["hash"] = $qmMap->map()->first()->hash;
            $qmMap["allowed_sides"] = array_map('intval', explode(',', $qmMap->allowed_sides));
            return $qmMap;
        });
    }
}
