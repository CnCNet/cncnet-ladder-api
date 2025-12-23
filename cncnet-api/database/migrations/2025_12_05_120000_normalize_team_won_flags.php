<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migration.
     *
     * Normalizes the 'won' flag for all players on winning teams in 2v2 matches.
     * Fixes historical data where a player died but their team won.
     */
    public function up(): void
    {
        Log::info('Starting migration: normalize_team_won_flags');

        // Get all 2v2 ladder types
        $twoVsTwoLadders = DB::table('ladders')
            ->where('ladder_type', '2vs2') // TWO_VS_TWO constant
            ->pluck('id');

        if ($twoVsTwoLadders->isEmpty()) {
            Log::info('No 2v2 ladders found, skipping migration');
            return;
        }

        Log::info('Found 2v2 ladders: ' . $twoVsTwoLadders->implode(', '));

        // Get all ladder histories for 2v2 ladders
        $ladderHistoryIds = DB::table('ladder_histories')
            ->whereIn('ladder_id', $twoVsTwoLadders)
            ->pluck('id');

        Log::info('Found ' . $ladderHistoryIds->count() . ' ladder histories for 2v2 ladders');

        // Get all best reports for 2v2 games (filter explicitly on best_report = true)
        $bestReportIds = DB::table('game_reports')
            ->join('games', 'games.id', '=', 'game_reports.game_id')
            ->whereIn('games.ladder_history_id', $ladderHistoryIds)
            ->where('game_reports.best_report', true)
            ->where('game_reports.valid', true)
            ->pluck('game_reports.id');

        Log::info('Found ' . $bestReportIds->count() . ' best reports for 2v2 games');

        $updatedCount = 0;
        $processedReports = 0;

        // Process in batches to avoid memory issues
        foreach ($bestReportIds->chunk(500) as $reportBatch) {
            foreach ($reportBatch as $reportId) {
                if ($reportId === null) {
                    continue;
                }

                $processedReports++;

                // Get all player game reports for this report
                $playerGameReports = DB::table('player_game_reports')
                    ->where('game_report_id', $reportId)
                    ->get();

                if ($playerGameReports->isEmpty()) {
                    continue;
                }

                // Find winning team (first non-spectator player who won)
                $winningTeam = null;
                foreach ($playerGameReports as $pgr) {
                    if ($pgr->won && !$pgr->spectator) {
                        $winningTeam = $pgr->team;
                        break;
                    }
                }

                // If no winning team found by 'won' flag, try fallback (non-defeated player)
                if ($winningTeam === null) {
                    foreach ($playerGameReports as $pgr) {
                        if (!$pgr->defeated && !$pgr->spectator) {
                            $winningTeam = $pgr->team;
                            break;
                        }
                    }
                }

                if ($winningTeam === null) {
                    continue; // No winner found, skip
                }

                // Update all players on winning team who don't have won=true
                foreach ($playerGameReports as $pgr) {
                    if ($pgr->spectator == false && $pgr->team == $winningTeam && !$pgr->won) {
                        DB::table('player_game_reports')
                            ->where('id', $pgr->id)
                            ->update(['won' => true]);
                        $updatedCount++;
                    }
                }
            }

            if ($processedReports % 1000 == 0) {
                Log::info("Processed $processedReports reports, updated $updatedCount player records");
            }
        }

        Log::info("Migration complete: Processed $processedReports reports, updated $updatedCount player game reports");
    }

    /**
     * Reverse the migration.
     *
     * Note: This cannot be perfectly reversed as we don't know which 'won' flags
     * were originally false. This is a data normalization migration.
     */
    public function down(): void
    {
        Log::warning('Cannot reverse normalize_team_won_flags migration - this is a data normalization');
        // Intentionally left empty - cannot reliably reverse this data change
    }
};
