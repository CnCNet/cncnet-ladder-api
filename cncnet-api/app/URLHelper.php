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
}
