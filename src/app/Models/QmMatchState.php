<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmMatchState extends Model
{

    //
    public function state()
    {
        return $this->belongsTo('App\Models\StateType', 'state_type_id');
    }

    public function qmMatch()
    {
        return $this->belongsTo('App\Models\QmMatch');
    }

    public function player()
    {
        return $this->belongsTo('App\Models\Player');
    }
}
