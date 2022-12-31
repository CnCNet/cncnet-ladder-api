<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameAudit extends Model
{
    protected $table = 'game_audit';

    public function game()
    {
        return $this->belongsTo('App\Game');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function ladderHistory()
    {
        return $this->belongsTo('App\LadderHistory');
    }

}
