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
        $createdAt = $this->created_at;

        // Convert timestamp to Carbon instance
        $createdAtCarbon = Carbon::parse($createdAt);

        // Calculate the difference in seconds from created_at to NOW
        $secondsDifference = now()->diffInSeconds($createdAtCarbon);

        return $secondsDifference;
    }
}
