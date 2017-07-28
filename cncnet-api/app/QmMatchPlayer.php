<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class QmMatchPlayer extends Model {

	//
    public function matches()
    {
        return $this->belongsTo('App\QmMatch');
    }

    public function readyMatch()
    {
        return $this->matches()->where('status', 'ready')->get();;
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }

    public function ladder()
    {
        return DB::where('App\Ladder');
    }
}
