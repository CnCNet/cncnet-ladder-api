<?php

namespace App\Http\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChartService
{
    public function getHistoriesGamesPlayedByMonth($histories, $ladderId)
    {
        # 1 day
        return Cache::remember("getHistoriesGamesPlayedByMonth/$ladderId", 1440, function () use ($histories)
        {
            $games = DB::table('games')
                ->whereIn("ladder_history_id", $histories->pluck("id")->toArray())
                ->select([
                    DB::raw('count(*) as count, HOUR(created_at) as hour'),
                    'ladder_history_id'
                ])
                ->groupBy('hour')
                ->get();

            $labels = [];
            $results = [];
            foreach ($games as $hour => $game)
            {
                $hourFormatted = Carbon::create(null, null, null, $hour);
                $hour = $hourFormatted->format('g:i A');

                $labels[] = $hour;
                $results[] = $game->count;
            }

            return [
                "labels" => $labels,
                "games" => $results,
            ];
        });
    }

    public function getPlayerGamesPlayedByMonth($player, $history)
    {
        return Cache::remember("getPlayerGamesPlayedByMonth/$history->short/$player->id", 10 * 60, function () use ($player, $history)
        {
            $now = $history->starts;
            $from = $now->copy()->startOfMonth()->toDateTimeString();
            $to = $now->copy()->endOfMonth()->toDateTimeString();

            // Use single query with DATE grouping instead of looping through each day
            $gamesByDate = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->selectRaw('
                    DATE(player_game_reports.created_at) as date,
                    SUM(CASE WHEN won = 1 THEN 1 ELSE 0 END) as won,
                    SUM(CASE WHEN won = 0 THEN 1 ELSE 0 END) as lost
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            // Fill in missing dates with zeros to maintain chart continuity
            $period = CarbonPeriod::create($from, $to);
            $results = [];
            foreach ($period as $date)
            {
                $dateKey = $date->format("Y-m-d");
                $results[$dateKey] = [
                    "won" => $gamesByDate->get($dateKey)?->won ?? 0,
                    "lost" => $gamesByDate->get($dateKey)?->lost ?? 0
                ];
            }

            $resultCollection = collect($results);

            return [
                "labels" => array_keys($results),
                "data_games_won" => $resultCollection->pluck("won"),
                "data_games_lost" => $resultCollection->pluck("lost"),
            ];
        });
    }

    public function getClanGamesPlayedByMonth($clan, $history)
    {
        return Cache::remember("getClanGamesPlayedByMonth/$history->short/$clan->id", 5 * 60, function () use ($clan, $history)
        {
            $now = $history->starts;
            $from = $now->copy()->startOfMonth()->toDateTimeString();
            $to = $now->copy()->endOfMonth()->toDateTimeString();

            $period = CarbonPeriod::create($from, $to);

            $results = [];
            foreach ($period as $date)
            {
                $dateKey = $date->format("Y-m-d");

                $s = $date->copy()->startOfDay();
                $e = $date->copy()->endOfDay();

                $wins = $clan->clanGames()
                    ->where("ladder_history_id", $history->id)
                    ->whereBetween("player_game_reports.created_at", [$s, $e])
                    ->where("won", true)
                    ->get();

                $wins = count($wins);

                $results[$dateKey]["won"] = $wins;

                $losses = $clan->clanGames()
                    ->where("ladder_history_id", $history->id)
                    ->whereBetween("player_game_reports.created_at", [$s, $e])
                    ->where("won", false)
                    ->get();

                $losses = count($losses);

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
