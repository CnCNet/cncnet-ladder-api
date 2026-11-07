<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameReplaySeals extends Model
{
    protected $table = 'game_replay_seals';

    protected $fillable = [
        'game_id',
        'player_id',
        'seals',
        'complete',
        'consistent',
    ];

    protected $casts = [
        'seals'      => 'array',
        'complete'   => 'boolean',
        'consistent' => 'boolean',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
