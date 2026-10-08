<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameReplay extends Model
{
    protected $table = 'game_replays';

    protected $fillable = [
        'game_id',
        'player_id',
        'user_id',
        'filename',
        'file_size',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
