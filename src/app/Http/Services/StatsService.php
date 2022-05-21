<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Cache;
use \App\Http\Services\LadderService;
use \App\Models\QmMatchPlayer;
use \App\Models\QmMatch;
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
}
