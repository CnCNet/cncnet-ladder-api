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
        // Calculate the difference between updated_at and created_at
        // updated_at is touched by matchup handlers when no match is found
        return $this->updated_at->diffInSeconds($this->created_at);
    }
}
