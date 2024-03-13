<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class LadderHistory extends Model
{
    protected $table = 'ladder_history';
    protected $dates = ['starts', 'ends'];
    public $timestamps = false;

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function hasEnded()
    {
        return $this->ends < Carbon::now();
    }
}
