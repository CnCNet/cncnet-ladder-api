<?php

namespace App\Http\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChartService
{
    public function getGamesPlayedByMonth($player, $history)
    {
        return Cache::remember("getGamesPlayedByMonth/$history->short/$player->id", 5, function () use ($player, $history)
        {
            $now = $history->starts;
            $from = $now->startOfMonth()->toDateTimeString();
            $to = $now->endOfMonth()->toDateTimeString();

            $period = CarbonPeriod::create($from, $to);

            $results = [];
            foreach ($period as $date)
            {
                $dateKey = $date->format("Y-m-d");

                $s = $date->copy()->startOfDay();
                $e = $date->copy()->endOfDay();

                $wins = $player->playerGames()
                    ->where("ladder_history_id", $history->id)
                    ->whereBetween("player_game_reports.created_at", [$s, $e])
                    ->where("won", true)
                    ->count();

                $results[$dateKey]["won"] = $wins;

                $losses = $player->playerGames()
                    ->where("ladder_history_id", $history->id)
                    ->whereBetween("player_game_reports.created_at", [$s, $e])
                    ->where("won", false)
                    ->count();

                $results[$dateKey]["lost"] = $losses;
            }

            $resultCollection = collect($results);

            return [
                "labels" => array_keys($results),
                "data_games_won" => $resultCollection->pluck("won"),
                "data_games_lost" => $resultCollection->pluck("lost"),
            ];
        });
    }
}
