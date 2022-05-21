<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmConnectionStats extends Model
{

    //

    public function player()
    {
        return $this->belongsTo('\App\Player');
    }
    public function ipAddress()
    {
        return $this->belongsTo('\App\IpAddress');
    }
}
