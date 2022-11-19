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
}
