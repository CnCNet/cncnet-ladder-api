<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        'App\Console\Commands\PruneRawLogs',
        'App\Console\Commands\PruneOldStats',
        'App\Console\Commands\UpdatePlayerCache',
        'App\Console\Commands\GenerateBulkRecords',
        'App\Console\Commands\UpdateIrc',
        'App\Console\Commands\CleanupQmMatchPlayers',
        'App\Console\Commands\CleanupQmMatches',
        'App\Console\Commands\CleanupGameReports',
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('cron_is_alive')->cron("* * * * *");

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
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
