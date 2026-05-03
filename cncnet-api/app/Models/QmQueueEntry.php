<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function secondsinQueue()
    {
        // Calculate the difference from created_at to updated_at
        // updated_at is touched by FindOpponentJob when matching starts
        return $this->created_at->diffInSeconds($this->updated_at);
    }
}
