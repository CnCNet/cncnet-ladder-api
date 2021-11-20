<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupQmMatchPlayers extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'QmMatchPlayers:prune';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Clean up QmMatchPlayers from the previous month';

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
		//Get QM Match Players from previous month
		$date = date('Y-m-d', strtotime(date('Y-m-1')));
		$quickMatchPlayers = \App\QmMatchPlayer::where('created_at', '<', $date);

		echo "Deleting " . $quickMatchPlayers->count() . " records from qm_match_players created before date " . $date;

		$quickMatchPlayers->delete();
	}
}
