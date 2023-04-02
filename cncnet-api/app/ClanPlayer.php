<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Clan;
use App\ClanRole;

class ClanPlayer extends Model
{
    protected $fillable = ['clan_id', 'player_id', 'clan_role_id'];

    public function setRoleAttribute($value)
    {
        $this->attributes['clan_role_id'] = ClanRole::firstOrCreate(['value' => $value])->id;
    }

    public function getRoleAttribute()
    {
        return $this->roleRelation !== null ? $this->roleRelation->value : '';
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }

    public function clan()
    {
        return $this->belongsTo('App\Clan');
    }

    public function roleRelation()
    {
        return $this->belongsTo('App\ClanRole', 'clan_role_id');
    }

    public function isOwner()
    {
        return $this->role == "Owner" || $this->player->user->isGod();
    }

    public function isManager()
    {
        return $this->role == "Manager";
    }

    public function isMember()
    {
        return $this->role == "Member";
    }
}
