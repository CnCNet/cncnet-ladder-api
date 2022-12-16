<?php

namespace App\Helpers;

class FactionHelper
{

    /**
     * Get a players commonly played faction by ladder
     * 
     * @param mixed $history 
     * @param mixed $playerCache 
     * @return mixed 
     */
    public static function getPlayersCommonlyPlayedFactionByHistory($history, $playerCache)
    {
        if ($history->ladder->game == 'yr')
        {
            $side = \App\Side::where('local_id', $playerCache->country)
                ->where('ladder_id', $history->ladder->id)
                ->first();
        }
        else
        {
            $side = \App\Side::where('local_id', $playerCache->side)
                ->where('ladder_id', $history->ladder->id)
                ->first();
        }

        $countryName = $side->name ?? '';
        return $countryName;
    }
}
