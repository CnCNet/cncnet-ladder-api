<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Side extends Model
{

    //
    public function ladder()
    {
        return $this->belongsTo('App\Models\Ladder');
    }
}
