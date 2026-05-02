<?php

namespace App\Helpers;

use App\Models\LadderHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Map;

class SiteHelper
{

    /**
     * 
     * @param mixed $history 
     * @return string 
     */
    public static function getLadderTypeFromHistory(LadderHistory $history)
    {
        if ($history->ladder->ladder_type == \App\Models\Ladder::ONE_VS_ONE)
        {
            return "1vs1";
        }
        elseif ($history->ladder->ladder_type == \App\Models\Ladder::TWO_VS_TWO)
        {
            return "2vs2";
        }
        else
        {
            return "Clan";
        }
    }

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
                return "";
            }

            // Use null-safe operators to prevent exceptions
            $imageHash = $map->image_hash ?: $map->hash;
            if (!$imageHash) {
                return "";
            }

            // Check history and ladder exist before accessing game property
            $game = $history?->ladder?->game;
            if (!$game) {
                return "";
            }

            $mapPreview = 'https://ladder.cncnet.org/images/maps/' . $game . '/' . $imageHash . '.png';
            return $mapPreview;
        }
        catch (Exception $ex)
        {
            // Log removed - was spamming production logs with empty values
            return "";
        }
    }

    public static function getMapPreviewUrlV2(string $game, Map $map)
    {
        try {
            if (!$map) {
                return "";
            }

            $imageHash = $map->image_hash ?: $map->hash;

            if (!$imageHash) {
                return "";
            }

            return "https://ladder.cncnet.org/images/maps/{$game}/{$imageHash}.png";
        } catch (Exception $ex) {
            // Log removed - was spamming production logs
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
