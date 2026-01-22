<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerInvitation extends Model 
{
    # player who sent the invitation
    public function author()
    {
        return $this->belongsTo(Player::class, 'author_player_id');
    }

    # player who was invited
    public function invitedPlayer()
    {
        return $this->belongsTo(Player::class, 'invited_player_id');
    }
}