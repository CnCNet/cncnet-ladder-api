<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\LadderHistory;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AprilFoolsPurge extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'april_purge';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Deletes the April 1st prank games';
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
    public function fire()
    {
        //
        $lh = LadderHistory::where('short', '=', '4-2021')->first();
        $games = Game::where('ladder_history_id', '=', $lh->id)
            ->get();

        foreach ($games as $game)
        {
            foreach ($game->playerGameReports as $pgr)
            {
                print $pgr;
                print "\n";
                if ($pgr->stats !== null)
                    $pgr->stats->delete();
                $pgr->delete();
            }
            print "$game\n";
            if ($game->report !== null)
            {
                $game->report->delete();
                print $game->report;
                print "\n";
            }
            $game->delete();
        }
    }
}
