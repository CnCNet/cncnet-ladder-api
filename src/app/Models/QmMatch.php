<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmMatch extends Model
{

    //
    public function players()
    {
        return $this->hasMany('App\QmMatchPlayer');
    }

    public function map()
    {
        return $this->belongsTo('App\QmMap', 'qm_map_id');
    }

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }

    public function states()
    {
        return $this->hasMany('App\QmMatchState');
    }

    public function qmConnectionStats()
    {
        return $this->hasMany('\App\QmConnectionStats');
    }
}
