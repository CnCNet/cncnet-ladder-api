<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameReplayStats extends Model
{
    protected $table = 'game_replay_stats';

    protected $fillable = [
        'game_id',
        'game_replay_id',
        'version',
        'stats',
    ];

    protected $casts = [
        'stats' => 'array',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function gameReplay()
    {
        return $this->belongsTo(GameReplay::class);
    }
}
