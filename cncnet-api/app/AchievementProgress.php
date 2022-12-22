<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AchievementProgress extends Model
{
    protected $table = 'achievements_progress';

    public function achievement()
    {
        return $this->belongsTo('App\Achievement');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
