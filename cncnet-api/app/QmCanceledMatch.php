<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class QmCanceledMatch extends Model {

    public function qmMatch()
    {
        return $this->belongsTo('App\QmMatch', 'qm_match_id');
    }

    public function player()
    {
        return $this->belongsTo('App\Player', 'player_id');
    }

    public function ladder()
    {
        return $this->belongsTo('App\Ladder', 'ladder_id');
    }
}
