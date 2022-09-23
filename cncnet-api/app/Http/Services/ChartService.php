<?php

namespace App\Http\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ChartService
{
    public function getGamesPlayedByMonth($player, $history)
    {
        $now = $history->starts;
        $from = $now->startOfMonth()->toDateTimeString();
        $to = $now->endOfMonth()->toDateTimeString();

        $queryGames = $player->playerGames()
            ->where("ladder_history_id", $history->id)
            ->whereBetween("player_game_reports.created_at", [$from, $to])
            ->select(
                DB::raw("DATE(player_game_reports.created_at) as playedDate"),
                DB::raw("count(*) as gamesTotal")
            )
            ->groupBy("playedDate")
            ->orderBy("playedDate", "DESC");

        $queryGamesWon = $player->playerGames()
            ->where("ladder_history_id", $history->id)
            ->whereBetween("player_game_reports.created_at", [$from, $to])
            ->where("won", true)
            ->select(
                DB::raw("DATE(player_game_reports.created_at) as playedDate"),
                DB::raw("count(*) as gamesTotalWon")
            )
            ->groupBy("playedDate")
            ->orderBy("playedDate", "DESC");

        $queryGamesLost = $player->playerGames()
            ->where("ladder_history_id", $history->id)
            ->whereBetween("player_game_reports.created_at", [$from, $to])
            ->where("won", false)
            ->select(
                DB::raw("DATE(player_game_reports.created_at) as playedDate"),
                DB::raw("count(*) as gamesTotalLost")
            )
            ->groupBy("playedDate")
            ->orderBy("playedDate", "DESC");

        return [
            "labels" => $queryGames->pluck("playedDate"),
            "data_games_total" => $queryGames->pluck("gamesTotal"),
            "data_games_won" => $queryGamesWon->pluck("gamesTotalWon"),
            "data_games_lost" => $queryGamesLost->pluck("gamesTotalLost"),
        ];
    }
}
