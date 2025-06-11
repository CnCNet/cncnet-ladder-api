<?php

namespace App\Helpers;

class ChartHelper
{
    public static function getChartColourByGameAbbreviation($abbreviation, $opacity = 1)
    {
        switch ($abbreviation)
        {
            case GameHelper::$GAME_RA:
                return "rgba(104, 0, 0, $opacity)";

            case GameHelper::$GAME_TS:
                return "rgba(212, 127, 0, $opacity)";

            case GameHelper::$GAME_RA2;
                return "rgba(255, 19, 19, $opacity)";

            case GameHelper::$GAME_YR;
                return "rgba(216, 0, 255, $opacity)";

            case GameHelper::$GAME_BLITZ;
                return "rgba(52, 152, 255, $opacity)";

            case GameHelper::$GAME_RA2;
                return "rgba(255, 19, 128, $opacity)";
        }
    }
}
