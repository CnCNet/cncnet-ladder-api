<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerActiveHandle extends Model
{
    protected $table = 'player_active_handles';

    public function __construct()
    {
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }
}