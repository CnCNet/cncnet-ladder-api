<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use App\Models\GameRaw;
use Illuminate\Support\Facades\Log;

class TestCronIsAlive extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cron_is_alive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check cron is alive';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        Log::info("Cron is indeed alive");
    }
}
