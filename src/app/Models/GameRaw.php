<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRaw extends Model
{
    protected $table = 'games_raw';
    protected $fillable = ['hash', 'packet', 'game_id', 'ladder_id', 'ctime'];
    public $timestamps = false;
}
