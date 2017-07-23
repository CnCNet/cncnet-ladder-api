<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerPoint extends Model 
{
    protected $table = 'player_points';
    public $timestamps = false;

    public function player()
    {
        return $this->belongsTo("App\Player");
    }
}
