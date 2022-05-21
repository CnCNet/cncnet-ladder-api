<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClanInvitation extends Model
{

    use SoftDeletes;

    //
    protected $fillable = ['clan_id', 'author_id', 'player_id', 'type'];
    protected $dates = ['deleted_at'];

    public function clan()
    {
        return $this->belongsTo('App\Models\Clan');
    }

    public function player()
    {
        return $this->belongsTo('App\Models\Player');
    }

    public function author()
    {
        return $this->belongsTo('App\Models\Player', 'author_id');
    }
}
