<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class AchievementTracker extends Model {

    protected $table = 'achievements_tracker';

    public function achievement()
	{
        return $this->belongsTo('App\Achievement');
	}

    public function user()
	{
        return $this->belongsTo('App\User');
	}    
}