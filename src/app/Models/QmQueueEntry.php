<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmQueueEntry extends Model
{

    //

    public function qmPlayer()
    {
        return $this->belongsTo('App\QmMatchPlayer', 'qm_match_player_id');
    }

    public function ladderHistory()
    {
        return $this->belongsTo('App\LadderHistory', 'ladder_history_id');
    }
}
