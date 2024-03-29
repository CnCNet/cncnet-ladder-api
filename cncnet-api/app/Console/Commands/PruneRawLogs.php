<?php namespace App\Console\Commands;

use App\Models\GameRaw;
use Illuminate\Console\Command;

class PruneRawLogs extends Command 
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'prune_logs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Prune raw logs';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */

    private $maxLogs = 500;

	public function handle()
	{
        $logs = GameRaw::count();

        if ($logs > $this->maxLogs)
        {
            $pruneNo = $logs - $this->maxLogs;
            $logs = GameRaw::orderBy("id", "asc")->take($pruneNo)->delete();
        }
	}
}