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
        $updatedAt = $this->updated_at;

        // Convert timestamps to Carbon instances
        $createdAtCarbon = Carbon::parse($createdAt);
        $updatedAtCarbon = Carbon::parse($updatedAt);

        // Calculate the difference in seconds
        $secondsDifference = $updatedAtCarbon->diffInSeconds($createdAtCarbon);

        return $secondsDifference;
    }
}
