<?php

namespace App;

use App\Helpers\GameHelper;
use Illuminate\Database\Eloquent\Model;

class ClanLadderRules extends Model
{
    public $timestamps = false;

    public function ladderRules()
    {
        return $this->belongsTo('App\Ladder');
    }

}
