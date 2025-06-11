<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    public $timestamps = false;

    protected $table = 'achievements';

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

}
