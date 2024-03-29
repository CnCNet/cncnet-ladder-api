<?php

namespace App\Helpers;

use App\Models\Side;

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
            $side = \App\Models\Side::where('local_id', $playerCache->country)
                ->where('ladder_id', $history->ladder->id)
                ->first();
        }
        else
        {
            $side = \App\Models\Side::where('local_id', $playerCache->side)
                ->where('ladder_id', $history->ladder->id)
                ->first();
        }

        $countryName = $side->name ?? '';
        return $countryName;
    }

    public static function getFactionCountryByHistory($history, $faction)
    {
        $side = Side::where('local_id', $faction)
            ->where('ladder_id', $history->ladder->id)
            ->first();
        return $side->name ?? "";
    }
}
