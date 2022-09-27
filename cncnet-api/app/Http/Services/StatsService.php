<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Cache;
use \App\Http\Services\LadderService;
use \App\QmMatchPlayer;
use \App\QmMatch;
use \Carbon\Carbon;

class StatsService
{
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
    }

    public function getQmStats($ladderAbbrev)
    {
        return Cache::remember("statsRequest/$ladderAbbrev", 5, function () use ($ladderAbbrev)
        {
            $timediff = Carbon::now()->subHour()->toDateTimeString();
            $ladder_id = $this->ladderService->getLadderByGame($ladderAbbrev)->id;
            $recentMatchedPlayers = QmMatchPlayer::where('created_at', '>', $timediff)
                ->where('ladder_id', '=', $ladder_id)
                ->count();
            $queuedPlayers = QmMatchPlayer::where('ladder_id', '=', $ladder_id)->whereNull('qm_match_id')->count();
            $recentMatches = QmMatch::where('created_at', '>', $timediff)
                ->where('ladder_id', '=', $ladder_id)
                ->count();

            $activeGames = QmMatch::where('updated_at', '>', Carbon::now()->subMinute(2))
                ->where('ladder_id', '=', $ladder_id)->count();

            $past24hMatches = QmMatch::where('updated_at', '>', Carbon::now()->subDay(1))
                ->where('ladder_id', '=', $ladder_id)->count();

            return [
                'recentMatchedPlayers' => $recentMatchedPlayers,
                'queuedPlayers' => $queuedPlayers,
                'past24hMatches' => $past24hMatches,
                'recentMatches' => $recentMatches,
                'activeMatches'   => $activeGames,
                'time'          => Carbon::now()
            ];
        });
    }

    public function getFactionsPlayedByPlayer($player, $history)
    {
        $now = $history->starts;
        $from = $now->startOfMonth()->toDateTimeString();
        $to = $now->endOfMonth()->toDateTimeString();

        $playerGames = $player->playerGames()
            ->where("ladder_history_id", $history->id)
            ->whereBetween("player_game_reports.created_at", [$from, $to])
            ->groupBy("sid")
            ->get();

        $factionResults = [];
        foreach ($playerGames as $pg)
        {
            $sideCountWon = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("cty", $pg->cty)
                ->where("won", true)
                ->count();

            $sideCountLost = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("cty", $pg->cty)
                ->where("won", false)
                ->count();

            $total = $player->playerGames()
                ->where("ladder_history_id", $history->id)
                ->whereBetween("player_game_reports.created_at", [$from, $to])
                ->where("cty", $pg->cty)
                ->count();

            $factionResults[$pg->cty] =
                [
                    "won" => $sideCountWon,
                    "lost" => $sideCountLost,
                    "total" => $total
                ];
        }
        return $factionResults;
    }
}
