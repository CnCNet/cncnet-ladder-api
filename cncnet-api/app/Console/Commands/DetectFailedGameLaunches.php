<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\QmCanceledMatch;
use App\Models\QmMatch;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DetectFailedGameLaunches extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'qm:detect-failed-launches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect QM games that were created but never launched (no player game reports)';

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
     * Finds games that:
     * 1. Have a qm_match_id (were created from Quick Match)
     * 2. Are at least 20 minutes old (to avoid false positives for games in progress)
     * 3. Have no player_game_reports (game never launched or crashed during loading)
     * 4. Haven't already been logged in qm_canceled_matches
     *
     * Note: False positives (long games) are auto-cleaned when reports arrive via saveLadderResult
     *
     * @return mixed
     */
    public function handle()
    {
        // Find games created from QM that are old enough and have no reports
        $timeThreshold = Carbon::now()->subMinutes(20);

        $failedGames = Game::whereNotNull('qm_match_id')
            ->where('created_at', '<', $timeThreshold)
            ->where('created_at', '>', Carbon::now()->subHours(2)) // Only look at last 2 hours
            ->whereDoesntHave('player_game_reports') // No reports submitted
            ->with(['qmMatch.map.map', 'qmMatch.players.player']) // Eager load for performance
            ->get();

        $recordsCreated = 0;

        // Batch check for already-logged matches to avoid N+1 queries
        $loggedMatchIds = QmCanceledMatch::where('reason', 'failed_launch')
            ->whereIn('qm_match_id', $failedGames->pluck('qm_match_id'))
            ->pluck('qm_match_id')
            ->toArray();

        foreach ($failedGames as $game) {
            // Check if we've already logged this failed launch
            if (in_array($game->qm_match_id, $loggedMatchIds)) {
                continue;
            }

            $qmMatch = $game->qmMatch;

            if (!$qmMatch) {
                continue;
            }

            // Build player data array with username and color
            $playerData = $qmMatch->players->map(function($qmPlayer) {
                return [
                    'username' => $qmPlayer->player->username ?? 'Unknown',
                    'color' => $qmPlayer->color
                ];
            })->values()->toArray();

            // Get all player usernames from this match
            $allPlayerUsernames = $qmMatch->players->pluck('player.username')->filter()->toArray();

            // Validate that we have at least some usernames
            if (empty($allPlayerUsernames)) {
                $this->warn("QM Match {$qmMatch->id} has no valid player usernames - skipping");
                continue;
            }

            // For failed launches, we don't know who specifically failed, so list all as "affected"
            // Leave canceled_by empty since no one explicitly canceled
            $canceledMatch = new QmCanceledMatch();
            $canceledMatch->qm_match_id = $qmMatch->id;
            $canceledMatch->player_id = null; // Unknown who caused the failure
            $canceledMatch->ladder_id = $qmMatch->ladder_id;
            $canceledMatch->map_name = $qmMatch->map->description ?? $qmMatch->map->map->name ?? 'Unknown';
            $canceledMatch->canceled_by_usernames = null; // No explicit cancellation
            $canceledMatch->affected_player_usernames = implode(',', $allPlayerUsernames);
            $canceledMatch->player_data = $playerData; // Model has array cast, auto json_encodes
            $canceledMatch->reason = 'failed_launch';
            $canceledMatch->save();

            $recordsCreated++;
        }

        $this->info("Detected and logged {$recordsCreated} failed game launches");

        return 0;
    }
}
