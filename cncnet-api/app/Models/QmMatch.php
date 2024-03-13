<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmMatch extends Model
{
    //
    public function players()
    {
        return $this->hasMany(QmMatchPlayer::class);
    }

    public function map()
    {
        return $this->belongsTo(QmMap::class, 'qm_map_id');
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function states()
    {
        return $this->hasMany(QmMatchState::class);
    }

    public function qmConnectionStats()
    {
        return $this->hasMany(QmConnectionStats::class);
    }
}
