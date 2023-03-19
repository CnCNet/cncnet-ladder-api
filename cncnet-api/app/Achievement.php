<?php

namespace App;

use App\Helpers\GameHelper;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    public $timestamps = false;

    protected $table = 'achievements';

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }

}
