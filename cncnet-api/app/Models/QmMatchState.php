<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmMatchState extends Model {

	//
    public function state()
    {
        return $this->belongsTo(StateType::class, 'state_type_id');
    }

    public function qmMatch()
    {
        return $this->belongsTo(QmMatch::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
