<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupQmMatches extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'QmMatches:prune';

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
        //Get QM Matches older than previous week
        $date = Carbon::now()->subWeek();
        $quickMatches = \App\Models\QmMatch::where('created_at', '<', $date);
        echo "Deleting " . $quickMatches->count() . " records from qm_matches created before date $date\n";
        $quickMatches->delete();


        // Delete orphaned qm_match_states
        $quickMatchStates = \App\Models\QmMatchState::leftJoin('qm_matches', function($join) {
            $join->on('qm_matches.id', '=', 'qm_match_states.qm_match_id');
        })->whereNull('qm_matches.id');

        echo "Deleting " . $quickMatchStates->count() . " qm_match_states where qm_match is null\n";

        $quickMatchStates->delete();
    }
}
