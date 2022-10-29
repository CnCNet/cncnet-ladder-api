<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerCache extends Model
{

    //
    public $timestamps = false;

    public function history()
    {
        return $this->belongsTo('App\LadderHistory', 'ladder_history_id');
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }

    public function rank()
    {
        $players = PlayerCache::where('ladder_history_id', '=', $this->history->id)->orderBy('points', 'desc')->get();
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
        $pcu = \App\PlayerCacheUpdate::where('player_cache_id', '=', $this->id)->first();

        if ($pcu === null)
        {
            $pcu = new \App\PlayerCacheUpdate;
            $pcu->player_cache_id = $this->id;
            $pcu->save();
        }
    }
}
