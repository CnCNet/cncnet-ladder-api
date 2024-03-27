<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AchievementProgress extends Model
{
    protected $table = 'achievements_progress';

    public function achievement()
    {
        return $this->belongsTo(Achievement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
