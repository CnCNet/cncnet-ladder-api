<?php

namespace App\Http\Controllers;

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