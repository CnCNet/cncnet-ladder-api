<?php

namespace App;

class URLHelper
{
    /**
     * Returns player profile url
     * @param mixed $history 
     * @param mixed $player 
     * @return string 
     */
    public static function getPlayerProfileUrl($history, $playerUsername)
    {
        return "/ladder/" . $history->short . "/" . $history->ladder->abbreviation . "/player/" . $playerUsername;
    }

    /**
     * Returns player profile url
     * @param mixed $history 
     * @param mixed $player 
     * @return string 
     */
    public static function getGameUrl($history, $gameId)
    {
        return "/ladder/" . $history->short . "/" . $history->ladder->abbreviation . "/games/" . $gameId;
    }

    /**
     * Returns ladder url from history obj
     * @param mixed $history 
     * @return string 
     */
    public static function getLadderUrl($history)
    {
        return "/ladder/" . $history->short . "/" . $history->ladder->abbreviation;
    }

    public static function getAccountLadderUrl($history)
    {
        return "/account/" . $history->ladder->abbreviation . "/list";
    }

    public static function getChampionsLadderUrl($history)
    {
        return "/ladder-champions/" . $history->abbreviation;
    }

    public static function getVideoUrlbyAbbrev($abbrev)
    {
        switch ($abbrev)
        {
            case "mo":
            case "yr":
            case "ra2":
            case "blitz":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/red-alert-2.mp4";

            case "ts":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/tiberian-sun.mp4";

            case "td":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/tiberian-dawn.mp4";

            case "d2k":
            case "ss":
            case "ra":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/red-alert-1.mp4";

            default:
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/tiberium-twighlight.mp4";
        }
    }

    public static function getVideoPosterUrlByAbbrev($abbrev)
    {
        switch ($abbrev)
        {
            case "mo":
            case "yr":
            case "ra2":
            case "blitz":
                return "/images/posters/red-alert-2.jpg";
                break;

            case "ts":
                return "/images/posters/tiberian-sun.jpg";

            case "d2k":
            case "ss":
            case "ra":
                return "/images/posters/red-alert-1.jpg";
        }
    }

    public static function getLadderLogoByAbbrev($abbrev)
    {
        return "/images/games/{$abbrev}/logo.png";
    }
}
