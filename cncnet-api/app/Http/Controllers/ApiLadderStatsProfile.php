<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ApiLadderStatsProfile extends Controller
{
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
    }

    private function getPlayer($game, $player)
    {
        // testing
        return [
            "id" => 124021,
            "username" => "DESOLATE",
            "points" => 358,
            "rank" => 48,
            "games_won" => 33,
            "game_count" => 65,
            "games_lost" => 32,
            "average_fps" => 56,
            "badge" => [
                "badge" => "badge-t5",
                "type" => "Captain"
            ]
        ];

        $date = Carbon::now()->format('m-Y');
        $ladderService = $this->ladderService;

        return Cache::remember("$date/$game/$player", 5, function () use ($ladderService, $date, $game, $player)
        {
            $history = $ladderService->getActiveLadderByDate($date, $game);
            return $ladderService->getLadderPlayer($history, $player);
        });
    }

    public function getWebview($game, $player)
    {
        if ($game == null || $player == null)
        {
            abort(404);
        }

        $player = $this->getPlayer($game, $player);
        return view("api.player-webview", ["player" => $player]);
    }
}
