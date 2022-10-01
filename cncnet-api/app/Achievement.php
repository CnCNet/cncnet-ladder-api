<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{

    protected $table = 'achievements';

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }
}
