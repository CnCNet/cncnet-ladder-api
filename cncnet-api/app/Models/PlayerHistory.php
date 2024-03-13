<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerHistory extends Model
{
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function ladderHistory()
    {
        return $this->belongsTo(LadderHistory::class);
    }
}
