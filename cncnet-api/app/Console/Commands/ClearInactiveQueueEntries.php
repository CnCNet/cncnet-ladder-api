<?php

namespace App\Console\Commands;

use App\Http\Controllers\ApiQuickMatchController;
use Illuminate\Console\Command;

class ClearInactiveQueueEntries extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'clear_inactive_queue_entries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears the qm queue entries';

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
        $controller = new ApiQuickMatchController();
        $controller->prunePlayersInQueue();
    }
}
