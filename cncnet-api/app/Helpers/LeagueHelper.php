<?php

namespace App\Helpers;

class LeagueHelper
{
    public static $CHAMPIONS_LEAGUE = 1;
    public static $CONTENDERS_LEAGUE = 2;

    public static function getLeagueNameByTier($tier)
    {
        switch ($tier)
        {
            case LeagueHelper::$CHAMPIONS_LEAGUE:
                return "Champions Players League";

            case LeagueHelper::$CONTENDERS_LEAGUE:
                return "Contenders Players League";
        }
    }

    public static function getLeagueIconByTier($tier)
    {
        switch ($tier)
        {
            case LeagueHelper::$CHAMPIONS_LEAGUE:
                return '<i class="bi bi-trophy league-icon"></i>';

            case LeagueHelper::$CONTENDERS_LEAGUE:
                return '<i class="bi bi-shield-slash-fill league-icon"></i>';
        }
    }
}
