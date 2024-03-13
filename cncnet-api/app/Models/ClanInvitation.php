<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClanInvitation extends Model {

    use SoftDeletes;

	//
    protected $fillable = [ 'clan_id', 'author_id', 'player_id', 'type' ];
    protected $dates = ['deleted_at'];

    public function clan()
    {
        return $this->belongsTo(Clan::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function author()
    {
        return $this->belongsTo(Player::class, 'author_id');
    }
}
