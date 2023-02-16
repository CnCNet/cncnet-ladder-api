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
        'App\Console\Commands\PruneRawLogs',
        'App\Console\Commands\PruneOldStats',
        'App\Console\Commands\UpdatePlayerCache',
        'App\Console\Commands\GenerateBulkRecords',
        'App\Console\Commands\UpdateIrc',
        'App\Console\Commands\AprilFoolsPurge',
        'App\Console\Commands\CleanupQmMatchPlayers',
        'App\Console\Commands\CleanupQmMatches',
        'App\Console\Commands\CleanupGameReports',
        'App\Console\Commands\UpdateStatsCache',
        'App\Console\Commands\CleanupQmCanceledMatches',
        'App\Console\Commands\UpdatePlayerRatings',
        'App\Console\Commands\ClearInactiveQueueEntries'
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
