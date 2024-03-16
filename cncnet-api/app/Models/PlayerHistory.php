<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerHistory extends Model
{
    protected $fillable = [
        'player_id',
        'ladder_history_id',
        'tier'
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function ladderHistory()
    {
        return $this->belongsTo(LadderHistory::class);
    }
}
