<?php

namespace App\Console\Commands;

use App\Models\StatsCache;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\LadderHistory;

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
        $ladderHistories = LadderHistory::whereBetween("starts", [$start, $start])
            ->whereBetween("ends", [$end, $end])
            ->join('ladders', 'ladder_history.ladder_id', '=', 'ladder.id')
            ->where('ladders.private', false)
            ->get();


        foreach ($ladderHistories as $history)
        {
            echo "\n Setting setPlayersTodayCache for ladder history id: $history->id";
            StatsCache::setPlayersTodayCache($history);

            // if ($history->ladder?->ladder_type == \App\Models\Ladder::CLAN_MATCH) // NULL pointer on ladder, how?
            // {
            //     echo "\n Setting setClansTodayCache for ladder history id: $history->id";
            //     StatsCache::setClansTodayCache($history);
            // }
        }
    }
}
