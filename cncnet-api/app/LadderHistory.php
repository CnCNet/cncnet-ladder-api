<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class LadderHistory extends Model
{
    protected $table = 'ladder_history';
    protected $dates = ['starts', 'ends'];
    public $timestamps = false;

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }

    public function games()
    {
        return $this->hasMany('App\Games');
    }

    public function hasEnded()
    {
        return $this->ends < Carbon::now();
    }
}
