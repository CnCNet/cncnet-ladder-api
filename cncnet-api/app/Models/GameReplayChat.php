<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameReplayChat extends Model
{
    protected $table = 'game_replay_chat';

    protected $fillable = [
        'game_id',
        'player_id',
        'version',
        'messages',
    ];

    protected $casts = [
        'messages' => 'array',
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
