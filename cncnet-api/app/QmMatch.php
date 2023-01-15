<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QmMatch extends Model
{

    public static $TYPE_QM_1VS1 = "QuickMatch1vs1";
    public static $TYPE_QM_1vs1_AI = "QuickMatch1vs1AI";
    public static $TYPE_QM_Coop_AI = "QuickMatchCoop";

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
