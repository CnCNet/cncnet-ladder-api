<?php

namespace App\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class SiteHelper
{
    /**
     * 
     * @param mixed $history - App\LadderHistory
     * @param mixed $map - App\Map
     * @return string 
     */
    public static function getMapPreviewUrl($history, $map, $hash)
    {
        try
        {
            if (!$map || $map == null)
            {
                Log::info("Null map found for hash='$hash'");
                return "";
            }

            $description = $map && $map !== null ? $map->description : "";
            $ladderName = $history && $history !== null ? $history->ladder->name : "";
            $imageHash = $map->image_hash;
            if ($imageHash == "") $imageHash = $map->hash;
            $mapPreview = 'https://ladder.cncnet.org/images/maps/' . $history->ladder->game . '/' . $imageHash . '.png';
            return $mapPreview;
        }
        catch (Exception $ex)
        {
            Log::info("Error fetching map preview url for map='$description', ladder='$ladderName', hash='$hash'");
            return "";
        }
    }

    public static function getEmojiByMonth()
    {
        $now = Carbon::now();
        $emoji = null;

        switch ($now->month)
        {
            case 10:
                $emoji = "🎃"; // Halloween
                break;
            case 11:
                $emoji = "🦃"; // Thanksgiving
                break;
            case 12:
                $emoji = "🎅";
                break;
        }

        return $emoji;
    }
}
