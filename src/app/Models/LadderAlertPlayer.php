<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LadderAlertPlayer extends Model
{

    //
    public function alert()
    {
        return $this->belongsTo('App\Models\LadderAlert');
    }

    public function player()
    {
        return $this->belongsTo('App\Models\Player');
    }
}
