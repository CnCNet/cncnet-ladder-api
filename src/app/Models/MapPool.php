<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapPool extends Model
{

    //

    public function ladder()
    {
        return $this->belongsTo('\App\Models\Ladder');
    }

    // Delete Me
    public function qmLadderRules()
    {
        return $this->belongsTo('\App\Models\QmLadderRules', 'qm_ladder_rules_id');
    }

    public function maps()
    {
        return $this->hasMany('\App\QmMap')->valid()->orderBy('bit_idx');
    }
}