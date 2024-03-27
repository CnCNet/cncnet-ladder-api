<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function clanCache($historyId)
    {
        return ClanCache::where('clan_id', '=', $this->clan_id)
            ->where("ladder_history_id", '=', $historyId)
            ->first();
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function clan()
    {
        return $this->belongsTo(Clan::class);
    }

    public function roleRelation()
    {
        return $this->belongsTo(ClanRole::class, 'clan_role_id');
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
