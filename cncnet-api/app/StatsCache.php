<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class StatsCache
{

    /**
     * Called by cron service only
     * @param mixed $history 
     * @return void 
     */
    public static function setPlayersTodayCache($history)
    {
        $now = Carbon::now();
        $from = $now->copy()->startOfDay()->toDateTimeString();
        $to = $now->copy()->endOfDay()->toDateTimeString();

        $playersToday =  PlayerGameReport::join("game_reports", "game_reports.id", "=", "player_game_reports.game_report_id")
            ->join("games", "games.id", "=", "game_reports.game_id")
            ->whereBetween("player_game_reports.created_at", [$from, $to])
            ->where("games.ladder_history_id", $history->id)
            ->where("game_reports.valid", true)
            ->groupBy("player_game_reports.player_id")
            ->pluck("player_game_reports.player_id");

        Cache::put("playersToday.$history->id", $playersToday, 1800 * 60);
    }


    /**
     * Called by cron service only
     * @param mixed $history 
     * @return void 
     */
    public static function setClansTodayCache($history)
    {
        $now = Carbon::now();
        $from = $now->copy()->startOfMonth()->toDateTimeString();
        $to = $now->copy()->endOfDay()->toDateTimeString();

        $clansToday =  PlayerGameReport::join("game_reports", "game_reports.id", "=", "player_game_reports.game_report_id")
            ->join("games", "games.id", "=", "game_reports.game_id")
            ->whereBetween("player_game_reports.created_at", [$from, $to])
            ->where("games.ladder_history_id", $history->id)
            ->where("game_reports.valid", true)
            ->groupBy("player_game_reports.clan_id")
            ->pluck("player_game_reports.clan_id");

        Cache::put("clansToday.$history->id", $clansToday, 1800 * 60);
    }

    /**
     * Retreived cached players today
     * @param mixed $history 
     * @return mixed 
     */
    public static function getPlayersTodayFromCache($history)
    {
        $cachedPlayers = Cache::get("playersToday.$history->id");

        if ($cachedPlayers)
        {
            return $cachedPlayers;
        }

        return [];
    }

    public static function getClansTodayFromCache($history)
    {
        $cachedClans = Cache::get("clansToday.$history->id");

        if ($cachedClans)
        {
            return $cachedClans;
        }

        return [];
    }
}
