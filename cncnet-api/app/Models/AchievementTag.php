<?php

namespace App\Models;

use App\Helpers\GameHelper;

class AchievementTag
{
    public function __construct()
    {
    }

    public static $WIN_RA_QM_GAMES = "Win Red Alert QM Games";
    public static $WIN_TS_QM_GAMES = "Win Tiberian Sun QM Games";
    public static $WIN_RA2_QM_GAMES = "Win Red Alert 2 QM Games";
    public static $WIN_YR_QM_GAMES = "Win Yuri's Revenge QM Games";
    public static $WIN_BLITZ_QM_GAMES = "Win Blitz QM Games";

    public static $WIN_FACTION_SOVIET_QM_GAMES = "Soviet: Win QM Games";
    public static $WIN_FACTION_ALLIED_QM_GAMES = "Allied: Win QM Games";
    public static $WIN_FACTION_YR_QM_GAMES = "Yuri: Win QM Games";
    public static $PLAY_GAMES_IN_ONE_MONTH = "Play Games in one Month";

    public static function getAchievementNameByTag($tag)
    {
        $achievementName = "";
        switch ($tag)
        {
            case AchievementTag::$WIN_FACTION_SOVIET_QM_GAMES:
                $achievementName = "soviet";
                break;

            case AchievementTag::$WIN_BLITZ_QM_GAMES:
            case AchievementTag::$WIN_YR_QM_GAMES:
            case AchievementTag::$WIN_FACTION_YR_QM_GAMES:
                $achievementName = "yuri";
                break;

            case AchievementTag::$WIN_FACTION_ALLIED_QM_GAMES:
                $achievementName = "allied";
                break;

            case AchievementTag::$WIN_RA_QM_GAMES:
                $achievementName = GameHelper::$GAME_RA;
                break;

            case AchievementTag::$WIN_TS_QM_GAMES:
                $achievementName = GameHelper::$GAME_TS;
                break;

            case AchievementTag::$WIN_RA2_QM_GAMES:
                $achievementName = GameHelper::$GAME_RA2;
                break;
        }

        return "achievement-$achievementName";
    }
}
