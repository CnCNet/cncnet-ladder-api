<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LadderAlertPlayer extends Model {

    //
    public function alert()
    {
        return $this->belongsTo(LadderAlert::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
