<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class MapPool extends Model {

    //

    public function qmLadderRules()
    {
        return $this->belongsTo('\App\QmLadderRules');
    }

    public function maps()
    {
        return $this->hasMany('\App\QmMap')->valid()->orderBy('bit_idx');
    }
}
