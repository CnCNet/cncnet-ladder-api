<?php

namespace App\Http\Controllers\v2;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;
use \App\Http\Services\GameService;
use \App\Http\Services\PlayerService;
use \App\Http\Services\AuthService;

class ApiLadderController extends Controller
{
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
    }

    public function pingLadder(Request $request)
    {
        return "pong";
    }

    public function getLadder(Request $request, $game = null)
    {
        return $this->ladderService->getLadderByGameAbbreviation($game);
    }

    public function getAllLadders(Request $request)
    {
        return $this->ladderService->getAllNonMigratedLadders();
    }

    public function getCurrentLadders(Request $request)
    {
        return $this->ladderService->getLadders(false);
    }

    public function getLadderGame(Request $request, $game = null, $gameId = null)
    {
        return $this->ladderService->getLadderGameById($game, $gameId);
    }
}
