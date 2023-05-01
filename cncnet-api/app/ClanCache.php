<?php

namespace App;

use App\Helpers\FactionHelper;
use Illuminate\Database\Eloquent\Model;

class ClanCache extends Model
{
    public $timestamps = false;

    public function getClanAvatar()
    {
        return $this->clan->getClanAvatar();
    }

    public function rank()
    {
        $players = ClanCache::where('ladder_history_id', '=', $this->history->id)
            ->orderBy('points', 'desc')
            ->get();

        $count = 1;

        foreach ($players as $player)
        {
            if ($player->id == $this->id)
                return $count;
            $count++;
        }
        return 0;
    }

    public function mark()
    {
        $pcu = ClanCacheUpdate::where('clan_cache_id', '=', $this->id)->first();

        if ($pcu === null)
        {
            $pcu = new ClanCacheUpdate();
            $pcu->clan_cache_id = $this->id;
            $pcu->save();
        }
    }

    public function mostPlayedFactionNameByLadderHistory($history)
    {
        return FactionHelper::getPlayersCommonlyPlayedFactionByHistory($history, $this);
    }


    # Relationships
    public function history()
    {
        return $this->belongsTo('App\LadderHistory', 'ladder_history_id');
    }

    public function clan()
    {
        return $this->belongsTo('App\Clan');
    }
}
