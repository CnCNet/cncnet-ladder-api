<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmQueueEntry extends Model
{

    public function qmPlayer()
    {
        return $this->belongsTo(QmMatchPlayer::class, 'qm_match_player_id');
    }

    public function ladderHistory()
    {
        return $this->belongsTo(LadderHistory::class, 'ladder_history_id');
    }
}
