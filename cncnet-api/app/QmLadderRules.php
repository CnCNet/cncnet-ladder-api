<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class QmLadderRules extends Model {

	//
    public function ladder()
    {
        return $this->belongsTo('\App\Ladder');
    }

    public function mapPool()
    {
        return $this->belongsTo('\App\MapPool');
    }

    public function mapPools()
    {
        return $this->hasMany('\App\MapPool');
    }

    public function all_sides()
    {
        return explode(',', $this->all_sides);
    }

    public function allowed_sides()
    {
        return explode(',', $this->allowed_sides);
    }
}
