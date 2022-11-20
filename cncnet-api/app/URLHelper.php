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
     * Returns ladder url from history obj
     * @param mixed $history 
     * @return string 
     */
    public static function getLadderUrl($history)
    {
        return "/ladder/" . $history->short . "/" . $history->ladder->abbreviation;
    }

    public static function getVideoUrlbyAbbrev($abbrev)
    {
        switch ($abbrev)
        {
            case "yr":
            case "ra2":
            case "blitz":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/red-alert-2.mp4";
                break;

            case "ts":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/tiberian-sun.mp4";

            case "ra":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/red-alert-1.mp4";
        }
    }
}
