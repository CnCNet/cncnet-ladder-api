<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmCanceledMatch extends Model {

    public function qmMatch()
    {
        return $this->belongsTo(QmMatch::class, 'qm_match_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class, 'ladder_id');
    }
}
