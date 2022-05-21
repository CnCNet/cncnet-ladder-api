<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\ClanRole;

class Clan extends Model
{

    //
    protected $fillable = ['ladder_id', 'short', 'name'];

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }

    public function clanPlayers()
    {
        return $this->hasMany('App\ClanPlayer')->orderBy('clan_role_id', 'ASC');
    }

    public function owners()
    {
        $ownerId = ClanRole::where('value', '=', 'Owner')->first()->id;
        return $this->clanPlayers()->where('clan_role_id', '=', $ownerId)->orderBy('updated_at', 'ASC');
    }

    public function managers()
    {
        $managerId = ClanRole::where('value', '=', 'manager')->first()->id;
        return $this->clanPlayers()->where('clan_role_id', '=', $managerId)->orderBy('updated_at', 'ASC');
    }

    public function members()
    {
        $memberId = ClanRole::where('value', '=', 'member')->first()->id;
        return $this->clanPlayers()->where('clan_role_id', '=', $memberId)->orderBy('updated_at', 'ASC');
    }

    public function invitations()
    {
        return $this->hasMany('App\ClanInvitation');
    }

    public function nextOwner($current = null)
    {
        if ($current !== null)
        {
            $excepted = $this->owners->filter(function ($p) use ($current)
            {
                return $p->id != $current->id;
            });
            if ($excepted->count() > 0)
            {
                return $excepted->first();
            }
        }

        $manager = $this->managers->first();
        return $manager !== null ? $manager : ($this->members !== null ? $this->members->first() : null);
    }
}
