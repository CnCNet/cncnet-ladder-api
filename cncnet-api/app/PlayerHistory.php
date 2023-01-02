<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerHistory extends Model
{
    public function player()
    {
        return $this->belongsTo('App\Player');
    }

    public function ladderHistory()
    {
        return $this->belongsTo('App\LadderHistory');
    }
}
