<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\PruneRawLogs::class,
        \App\Console\Commands\PruneOldStats::class,
        \App\Console\Commands\UpdatePlayerCache::class,
        \App\Console\Commands\UpdateClanCache::class,
        \App\Console\Commands\GenerateBulkRecords::class,
        \App\Console\Commands\UpdateIrc::class,
        \App\Console\Commands\AprilFoolsPurge::class,
        \App\Console\Commands\CleanupQmMatchPlayers::class,
        \App\Console\Commands\CleanupQmMatches::class,
        \App\Console\Commands\CleanupGameReports::class,
        \App\Console\Commands\UpdateStatsCache::class,
        \App\Console\Commands\CleanupQmCanceledMatches::class,
        \App\Console\Commands\UpdatePlayerRatings::class,
        \App\Console\Commands\ClearInactiveQueueEntries::class,
        \App\Console\Commands\ForceUpdateClanCache::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('prune_logs')
            ->daily();
        $schedule->command('prune_stats')
            ->daily();
        $schedule->command('update_player_cache')
            ->hourly();
        $schedule->command('update_clan_cache')
            ->hourly();
        $schedule->command('QmMatchPlayers:prune')
            ->monthly();
        $schedule->command('QmMatches:prune')
            ->monthly();
        $schedule->command('GameReports:prune')
            ->monthly();
        $schedule->command('update_stats_cache')
            ->hourly();
        $schedule->command('QmCanceledMatches:prune')
            ->monthly();
        $schedule->command('update_player_ratings')
            ->monthly();

        $schedule->command('clear_inactive_queue_entries')->everyMinute();
    }
}
