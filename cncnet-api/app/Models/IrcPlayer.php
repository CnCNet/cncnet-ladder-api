<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IrcPlayer extends Model
{
    protected $connection = "irc";

    //
    protected $fillable = ['player_id', 'ladder_id', 'username'];
}
