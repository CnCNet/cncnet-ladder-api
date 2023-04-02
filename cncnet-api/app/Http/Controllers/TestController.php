<?php

namespace App\Http\Controllers;

use App\GameReport;
use \App\Http\Services\LadderService;
use App\PlayerGameReport;

class TestController extends Controller
{
    public function test()
    {
        $ladderService = new LadderService();
        $gameReport = GameReport::find(1052218);
        $ladderService->updatePlayerCache($gameReport);
    }
}
