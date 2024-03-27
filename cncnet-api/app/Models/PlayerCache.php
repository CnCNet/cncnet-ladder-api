<?php

namespace App\Models;

use App\Helpers\FactionHelper;
use Illuminate\Database\Eloquent\Model;

class PlayerCache extends Model
{
    //
    public $timestamps = false;

    public function history()
    {
        return $this->belongsTo(LadderHistory::class, 'ladder_history_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function rank()
    {
        $players = PlayerCache::where('ladder_history_id', '=', $this->history->id)
            ->where("tier", $this->tier)
            ->orderBy('points', 'desc')
            ->get();

        $count = 1;

        foreach ($players as $player)
        {
            if ($player->id == $this->id)
                return $count;
            $count++;
        }
        return 9999;
    }

    public function mark()
    {
        $pcu = PlayerCacheUpdate::where('player_cache_id', '=', $this->id)->first();

        if ($pcu === null)
        {
            $pcu = new PlayerCacheUpdate;
            $pcu->player_cache_id = $this->id;
            $pcu->save();
        }
    }

    public function mostPlayedFactionNameByLadderHistory($history)
    {
        return FactionHelper::getPlayersCommonlyPlayedFactionByHistory($history, $this);
    }
}
