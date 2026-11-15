<?php

namespace App\Console\Commands;

use App\Models\Ladder;
use App\Models\Player;
use App\Models\QmMap;
use App\Models\QmMapPick;
use App\Models\QmMatchPlayer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateMapPicksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:map-picks {--dry-run : Run without saving changes} {--ladder= : Migrate specific ladder only} {--all : Include all users (default: only users active in last 60 days)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy map_sides data to qm_map_picks table (defaults to active users only)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $ladderId = $this->option('ladder');
        $includeAll = $this->option('all');

        $this->info('Starting map picks migration...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
        }

        if (!$includeAll) {
            $this->info('Filtering to users active in last 60 days (use --all to include everyone)');
        }

        // Get all QmMatchPlayers with map_sides_id, grouped by user and ladder
        $query = QmMatchPlayer::whereNotNull('map_sides_id')
            ->with(['player.user', 'ladder', 'mapSides'])
            ->select('qm_match_players.player_id', 'qm_match_players.ladder_id', 'qm_match_players.map_sides_id')
            ->distinct();

        if ($ladderId) {
            $query->where('qm_match_players.ladder_id', $ladderId);
        }

        // Filter to active users only (QM match in last 60 days) unless --all specified
        if (!$includeAll) {
            $cutoffDate = now()->subDays(60);
            $query->join('qm_matches', 'qm_match_players.qm_match_id', '=', 'qm_matches.id')
                ->where('qm_matches.created_at', '>=', $cutoffDate);
        }

        $matchPlayers = $query->get();

        if ($matchPlayers->isEmpty()) {
            $this->info('No legacy map_sides data found to migrate.');
            return 0;
        }

        $this->info("Found {$matchPlayers->count()} player-ladder combinations to migrate");

        $bar = $this->output->createProgressBar($matchPlayers->count());
        $bar->start();

        $stats = [
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($matchPlayers as $matchPlayer) {
            try {
                $result = $this->migratePlayerMapPicks($matchPlayer, $dryRun);
                $stats['processed']++;
                $stats['created'] += $result['created'];
                $stats['skipped'] += $result['skipped'];
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->newLine();
                $this->error("Error migrating player {$matchPlayer->player_id} on ladder {$matchPlayer->ladder_id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Migration complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Players processed', $stats['processed']],
                ['Picks created', $stats['created']],
                ['Picks skipped (already exist)', $stats['skipped']],
                ['Errors', $stats['errors']],
            ]
        );

        return 0;
    }

    /**
     * Migrate map picks for a single player-ladder combination
     */
    private function migratePlayerMapPicks(QmMatchPlayer $matchPlayer, bool $dryRun): array
    {
        $player = $matchPlayer->player;
        if (!$player || !$player->user) {
            throw new \Exception("Player or user not found");
        }

        $user = $player->user;
        $ladder = $matchPlayer->ladder;
        $mapSidesValue = $matchPlayer->mapSides->value ?? null;

        if (!$mapSidesValue) {
            return ['created' => 0, 'skipped' => 0];
        }

        // Parse CSV (e.g., "0,5,-1,2")
        $sideChoices = explode(',', $mapSidesValue);

        // Get ladder's qm_maps ordered by bit_idx
        $qmMaps = QmMap::where('ladder_id', $ladder->id)
            ->orderBy('bit_idx')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($sideChoices as $bitIdx => $sideIndex) {
            // Find map with this bit_idx
            $qmMap = $qmMaps->firstWhere('bit_idx', $bitIdx);

            if (!$qmMap) {
                // No map for this bit position, skip
                continue;
            }

            $sideIndex = (int) $sideIndex;

            // Validate side_index
            if (!QmMapPick::validateSideIndex($sideIndex, $ladder)) {
                $this->warn("Invalid side_index {$sideIndex} for map {$qmMap->id}, skipping");
                continue;
            }

            if (!$dryRun) {
                // Check if pick already exists
                $existing = QmMapPick::where('user_id', $user->id)
                    ->where('ladder_id', $ladder->id)
                    ->where('qm_map_id', $qmMap->id)
                    ->exists();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Create new pick
                QmMapPick::create([
                    'user_id' => $user->id,
                    'ladder_id' => $ladder->id,
                    'qm_map_id' => $qmMap->id,
                    'side_index' => $sideIndex,
                ]);

                $created++;
            } else {
                $created++; // Count what would be created in dry run
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
