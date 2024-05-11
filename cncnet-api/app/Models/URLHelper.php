<?php

namespace App\Models;

use Illuminate\Support\Facades\Vite;

class URLHelper
{
    public static function getLadderLeague($history, $tier)
    {
        return "/ladder/" . $history->short . "/" . $tier . "/" . $history->ladder->abbreviation;
    }

    public static function getLadderChampionsUrl($abbreviation, $tier)
    {
        return "/ladder-champions/" . $abbreviation . "?tier=" . $tier;
    }

    /**
     * 
     * @param mixed $history 
     * @param mixed $playerUsername 
     * @return string 
     */
    public static function getPlayerProfileUrl($history, $playerUsername)
    {
        return "/ladder/" . $history->short . "/" . $history->ladder->abbreviation . "/player/" . $playerUsername;
    }



    /**
     * 
     * @param mixed $history 
     * @param mixed $playerUsername 
     * @return string 
     */
    public static function getPlayerProfileAchievementsUrl($history, $playerUsername)
    {
        return URLHelper::getPlayerProfileUrl($history, $playerUsername) . "/achievements";
    }


    /**
     * Get ladder game url
     * @param mixed $history 
     * @param mixed $gameId 
     * @return string 
     */
    public static function getGameUrl($history, $gameId)
    {
        return "/ladder/" . $history->short . "/" . $history->ladder->abbreviation . "/games/" . $gameId;
    }

    /**
     * Get ladder game url
     * @param mixed $history 
     * @param mixed $gameId 
     * @return string 
     */
    public static function getWashGamesUrl($ladderAbbrev)
    {
        return "/admin/washedGames/" . $ladderAbbrev;
    }

    /**
     * Return ladder url 
     * @param mixed $history 
     * @return string 
     */
    public static function getLadderUrl($history)
    {
        return "/ladder/" . $history->short . "/" . $history->ladder->abbreviation;
    }

    public static function getClanProfileUrl($history, $clanShort)
    {
        return "/ladder/" . $history->short . "/" . $history->ladder->abbreviation . "/clan/" . $clanShort;
    }

    public static function getClanProfileLadderUrl($history, $clanId)
    {
        $clan = Clan::find($clanId);
        if ($clan == null)
        {
            return null;
        }
        return "/ladder/" . $history->short . "/" . $history->ladder->abbreviation . "/clan/" . $clan->short;
    }

    /**
     * 
     * @param mixed $history 
     * @return string 
     */
    public static function getAccountLadderUrl($history)
    {
        return "/account/" . $history->ladder->abbreviation . "/list";
    }

    /**
     * 
     * @param mixed $history 
     * @return string 
     */
    public static function getChampionsLadderUrl($history)
    {
        return "/ladder-champions/" . $history->abbreviation;
    }

    /**
     * 
     * @param mixed $abbrev 
     * @return string|void 
     */
    public static function getVideoUrlbyAbbrev($abbrev)
    {
        switch ($abbrev)
        {
            case "yr":
            case "ra2":
            case "ra2-new-maps":
            case "ra2-cl":
            case "blitz":
            case "blitz-2v2":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/red-alert-2.mp4";

            case "ts":
            case "ts-cl":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/tiberian-sun.mp4";

            case "ra":
            case "ra-cl":
            case "ra-2v2":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.4/red-alert-1.mp4";

            case "d2k":
                return "//cdn.jsdelivr.net/gh/cnc-community/files@1.9/dune.mp4";
        }
    }

    /**
     * 
     * @param mixed $abbrev 
     * @return string|void 
     */
    public static function getVideoPosterUrlByAbbrev($abbrev)
    {
        switch ($abbrev)
        {
            case "yr":
            case "ra2":
            case "ra2-cl":
            case "ra2-new-maps":
            case "blitz":
            case "blitz-2v2":
                return Vite::asset("resources/images/posters/red-alert-2.jpg");

            case "ts":
            case "ts-cl":
                return Vite::asset("resources/images/posters/tiberian-sun.jpg");

            case "ra":
            case "ra-cl":
            case "ra-2v2":
            case "d2k":
                return Vite::asset("resources/images/posters/red-alert-1.jpg");
        }
    }

    /**
     * 
     * @param mixed $abbrev 
     * @return string 
     */
    public static function getLadderLogoByAbbrev($abbrev)
    {
        switch ($abbrev)
        {
            case "ra2":
            case "ra2-cl":
            case "ra2-new-maps":
                return Vite::asset("resources/images/games/ra2/logo.png");

            case "blitz":
            case "blitz-2v2":
                return Vite::asset("resources/images/games/blitz/logo.png");

            case "ts":
            case "ts-cl":
                return Vite::asset("resources/images/games/ts/logo.png");

            case "ra":
            case "ra-cl":
            case "ra-2v2":
                return Vite::asset("resources/images/games/ra/logo.png");

            default:
                return Vite::asset("resources/images/games/{$abbrev}/logo.png");
        }
    }

    /**
     * 
     * @param mixed $abbrev 
     * @return string 
     */
    public static function getLadderIconByAbbrev($abbrev)
    {
        switch ($abbrev)
        {
            case "ra2":
            case "ra2-cl":
            case "ra2-new-maps":
                return Vite::asset("resources/images/games/ra2/ra2-icon.png");

            case "blitz":
            case "blitz-2v2":
                return Vite::asset("resources/images/games/blitz/blitz-icon.png");

            case "ts":
            case "ts-cl":
                return Vite::asset("resources/images/games/ts/ts-icon.png");

            case "ra":
            case "ra-cl":
                return Vite::asset("resources/images/games/ra/ra-icon.png");

            default:
                return Vite::asset("resources/images/games/{$abbrev}/$abbrev-icon.png");
        }
    }
}
