<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClanInvitation extends Model {

    use SoftDeletes;

	//
    protected $fillable = [ 'clan_id', 'author_id', 'player_id', 'type' ];
    protected $dates = ['deleted_at'];

    public function clan()
    {
        return $this->belongsTo('App\Clan');
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }

    public function author()
    {
        return $this->belongsTo('App\Player', 'author_id');
    }
}
