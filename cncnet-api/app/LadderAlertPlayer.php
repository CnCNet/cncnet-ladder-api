<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class LadderAlertPlayer extends Model {

    //
    public function alert()
    {
        return $this->belongsTo('App\LadderAlert');
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }
}
