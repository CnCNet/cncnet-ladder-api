<?php

namespace App\Http\Controllers;

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

        if (isset($player["error"]))
        {
            return $player["error"];
        }

        return view("api.player-webview", [
            "player" => json_decode(json_encode($player))
        ]);
    }
}
