<?php namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupGameReports extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'GameReports:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes secondary GameReports and dependant stats.';

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
        // Deletes the unused GameReports and the associated stats.
        $date = Carbon::now()->startOfMonth();
        $gameReports = \App\Models\GameReport::where('created_at', '<', $date)->where('best_report', '=', false);

        echo "Deleting " . $gameReports->count() . " game_reports where best_report = false and older than $date\n";

        // The loop version is slower but it doesn't slam the CPU as hard as the leftJoin version
        $pgrCount = 0;
        $statsCount = 0;
        $gameObjects = 0;
        $gameReports->chunk(200, function($grs) use (&$pgrCount, &$statsCount, &$gameObjects)
        {
            foreach ($grs as $gr)
            {
                foreach ($gr->playerGameReports as $pgr)
                {
                    $pgrCount++;

                    if ($pgr->stats)
                    {
                        $statsCount++;

                        $gameObjects += $pgr->stats->gameObjectCounts->count();
                        $pgr->stats->gameObjectCounts()->delete();
                        $pgr->stats->delete();
                    }
                    $pgr->delete();
                }
            }
            echo "Deleted $pgrCount PlayerGameReports, $statsCount Stats, $gameObjects GameObjectCounts\n";

            // Be extra nice during the cleanup
            sleep(10);
        });
        $gameReports = \App\Models\GameReport::where('created_at', '<', $date)->where('best_report', '=', false);
        $gameReports->delete();

        /*

        //This version is much faster but it takes a lot of CPU

        $gameReports->delete();
        echo "Done\n";

        $playerGameReports = \App\PlayerGameReport::leftJoin('game_reports', function($join)
        {
            $join->on('game_reports.id', '=', 'player_game_reports.game_report_id');
        })->whereNull('game_reports.id');

        echo "Deleting " . $playerGameReports->count() . " player_game_reports where game_report_id was null\n";
        $playerGameReports->delete();
        echo "Done\n";

        $stats = \App\Stats2::leftJoin('player_game_reports', function($join)
        {
            $join->on('player_game_reports.stats_id', '=', 'stats2.id');
        })->whereNull('player_game_reports.id');

        echo "Deleting " . $stats->count() . " stats where player_game_reports was null\n";
        $stats->delete();
        echo "Done\n";

        $gameObjects = \App\GameObjectCounts::leftJoin('stats2', function ($join)
        {
            $join->on('stats2.id', '=', 'game_object_counts.stats_id');
        })->whereNull('stats2.id');

        echo "Deleting " . $gameObjects->count() . " game_object_counts where stats_id was null\n";
        $gameObjects->delete();
        echo "Done\n"; */
    }

}
