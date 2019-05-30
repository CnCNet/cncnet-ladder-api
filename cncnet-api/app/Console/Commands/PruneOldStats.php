<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PruneOldStats extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:prune_stats';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Deletes stats.dmp files that are older than 30 days';

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
		//
        $dmpPath = config('filesystems')['dmp'];
        shell_exec("/usr/bin/find $dmpPath -mtime +30 -delete");
	}
}
