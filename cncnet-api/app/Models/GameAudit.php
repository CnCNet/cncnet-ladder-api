<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameAudit extends Model
{
    protected $table = 'game_audit';

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ladderHistory()
    {
        return $this->belongsTo(LadderHistory::class);
    }

}
