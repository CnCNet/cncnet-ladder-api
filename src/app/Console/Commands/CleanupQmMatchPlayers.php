<?php

namespace App\Console\Commands;

use App\Models\MapSideString;
use App\Models\QmMatchPlayer;
use Illuminate\Console\Command;
use Carbon\Carbon;

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
		//Get QM Match Players older than one week
		$date = Carbon::now()->subWeek();
		$quickMatchPlayers = QmMatchPlayer::where('created_at', '<', $date);

		echo "Deleting " . $quickMatchPlayers->count() . " records from qm_match_players created before date $date\n";

		$quickMatchPlayers->delete();

		$mapSides = MapSideString::leftJoin('qm_match_players', function ($join)
		{
			$join->on('qm_match_players.map_sides_id', '=', 'map_side_strings.id');
		})->whereNull('qm_match_players.map_sides_id');

		echo "Deleting " . $mapSides->count() . " map_side_strings where qm_match_players.id is null\n";

		$mapSides->delete();
	}
}
