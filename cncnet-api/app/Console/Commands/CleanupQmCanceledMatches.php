<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupQmCanceledMatches extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'QmCanceledMatches:prune';

	/**
     * Prune old QmMatches.
     *
     * @var string
     */
    protected $description = 'Command description.';

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
        //Get QM Canceled Matches older than previous month
        $date = Carbon::now()->subMonth();
        $canceledQuickMatches = \App\Models\QmCanceledMatch::where('created_at', '<', $date);
        echo "Deleting " . $canceledQuickMatches->count() . " records from qm_canceled_matches created before date: " . $date;
        $canceledQuickMatches->delete();
    }
}
