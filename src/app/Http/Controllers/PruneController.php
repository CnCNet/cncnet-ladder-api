<?php

namespace App\Http\Controllers;

use \Carbon\Carbon;
use App\Models\LadderHistory;
use Illuminate\Http\Request;
use \App\Http\Services\LadderService;

class PruneController extends Controller
{
    private $maxLogs = 500;
    public function pruneLogs()
    {
        $logs = \App\Models\GameRaw::count();

        if ($logs > $this->maxLogs)
        {
            $pruneNo = $logs - $this->maxLogs;
            $logs = \App\Models\GameRaw::orderBy("id", "asc")->take($pruneNo)->delete();
        }
    }
}
