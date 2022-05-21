<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Models\IrcHostmask;
use App\Models\IrcPlayer;
use App\Models\Ladder;
use App\Models\PlayerActiveHandle;
use DB;
use \Carbon\Carbon;

class IrcAssociation extends Model
{

    //
    protected $fillable = ['user_id', 'ladder_id', 'player_id', 'clan_id', 'irc_hostmask'];
    protected $dates = ['created_at', 'updated_at', 'refreshed_at'];

    protected $hidden = ['created_at', 'updated_at'];

    public static function findOrCreate($user_id, $hostmask, $refresh = true)
    {
        $hm = IrcHostmask::firstOrCreate(['value' => $hostmask]);

        $a = null;
        foreach (Ladder::all() as $ladder)
        {
            $ph = PlayerActiveHandle::select(DB::Raw('player_id, MAX(updated_at) as updated_at'))
                ->where('user_id', '=', $user_id)
                ->where('ladder_id', '=', $ladder->id)
                ->groupBy('player_id')
                ->first();

            if ($ph !== null)
            {
                $ip = IrcPlayer::firstOrNew([
                    'player_id' => $ph->player_id,
                    'ladder_id' => $ladder->id
                ]);
                $ip->username = $ph->player->username;
                $ip->save();

                $clanId = $ph->player->clanPlayer ? $ph->player->clanPlayer->clan->id : 0;

                $a = IrcAssociation::firstOrNew([
                    'user_id' => $user_id,
                    'ladder_id' => $ladder->id
                ]);

                $a->player_id = $ph->player_id;
                $a->clan_id = $clanId;
                $a->irc_hostmask_id = $hm->id;
                if ($refresh)
                    $a->refreshed_at = Carbon::now();
                $a->touch();
                $a->save();
            }
        }

        return $a;
    }

    public function setIrcHostmaskAttribute($value)
    {
        $this->attributes['irc_hostmask_id'] = IrcHostmask::firstOrCreate(['value' => $value]);
    }

    public function getIrcHostmaskAttribute()
    {
        return $this->ircHostmaskRelation !== null ? $this->ircHostmaskRelation->value : '';
    }

    public function ircHostmaskRelation()
    {
        return $this->belongsTo('App\Models\IrcHostmask', 'irc_hostmask_id');
    }

    public function scopeLoggedIn($query)
    {
        return $query->where('refreshed_at', '>', Carbon::now()->subHour(24));
    }

    public function scopeWhereLadder($query, $ladder_id)
    {
        return $query->where('ladder_id', '=', $ladder_id);
    }
}
