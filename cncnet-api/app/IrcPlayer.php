<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class IrcPlayer extends Model {

    //
    protected $fillable = [ 'player_id', 'ladder_id', 'username' ];
}
