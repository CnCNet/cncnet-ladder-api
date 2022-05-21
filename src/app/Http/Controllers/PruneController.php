<?php

namespace App\Http\Controllers;

use \Carbon\Carbon;
use App\LadderHistory;
use Illuminate\Http\Request;
use \App\Http\Services\LadderService;

class PruneController extends Controller
{
    private $maxLogs = 500;
    public function pruneLogs()
    {
        $logs = \App\GameRaw::count();

        if ($logs > $this->maxLogs)
        {
            $pruneNo = $logs - $this->maxLogs;
            $logs = \App\GameRaw::orderBy("id", "asc")->take($pruneNo)->delete();
        }
    }
}
