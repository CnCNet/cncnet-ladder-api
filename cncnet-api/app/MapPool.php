<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class MapPool extends Model {

    //

    public function ladder()
    {
        return $this->belongsTo('\App\Ladder');
    }

    // Delete Me
    public function qmLadderRules()
    {
        return $this->belongsTo('\App\QmLadderRules', 'qm_ladder_rules_id');
    }

    public function maps()
    {
        return $this->hasMany('\App\QmMap')->valid()->orderBy('bit_idx');
    }

    public function tiers()
    {
        return $this->hasMany('App\MapTier', 'map_pool_id');
    }
}
