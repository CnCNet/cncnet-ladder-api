<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillGameMapIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:backfill-map-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill qm_map_id for existing games from their qm_matches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill of qm_map_id for games...');

        // Count games that need backfilling
        $totalGames = DB::table('games')
            ->whereNotNull('qm_match_id')
            ->whereNull('qm_map_id')
            ->count();

        if ($totalGames === 0) {
            $this->info('No games need backfilling.');
            return 0;
        }

        $this->info("Found {$totalGames} games that need backfilling.");

        // Use join update approach for better performance
        // Update games set qm_map_id = qm_matches.qm_map_id where games.qm_match_id = qm_matches.id
        $updated = DB::table('games')
            ->join('qm_matches', 'games.qm_match_id', '=', 'qm_matches.id')
            ->whereNull('games.qm_map_id')
            ->whereNotNull('qm_matches.qm_map_id')
            ->update(['games.qm_map_id' => DB::raw('qm_matches.qm_map_id')]);

        $this->info("Updated {$updated} games with qm_map_id from their qm_matches.");

        // Count remaining games without map data (qm_match was already pruned)
        $remaining = DB::table('games')
            ->whereNotNull('qm_match_id')
            ->whereNull('qm_map_id')
            ->count();

        if ($remaining > 0) {
            $this->warn("WARNING: {$remaining} games still have no qm_map_id (their qm_match was already pruned).");
            $this->warn("These games cannot be backfilled and will not appear in map statistics.");
        }

        $this->info('Backfill complete!');
        return 0;
    }
}
