<?php

namespace App\Console\Commands;

use App\LadderHistory;
use App\StatsCache;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdateStatsCache extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'update_stats_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the stats cache on disk';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Current ladder month only
        $now = Carbon::now();
        $start = $now->startOfMonth()->toDateTimeString();
        $end = $now->endOfMonth()->toDateTimeString();
        $ladderHistories = \App\LadderHistory::whereBetween("starts", [$start, $start])
            ->whereBetween("ends", [$end, $end])
            ->get();


        foreach ($ladderHistories as $history)
        {
            echo "\n Setting setPlayersTodayCache for ladder history id: $history->id";
            StatsCache::setPlayersTodayCache($history);
        }
    }
}
