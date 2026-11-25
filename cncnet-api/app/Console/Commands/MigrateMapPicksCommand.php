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

        // Get the MOST RECENT QmMatchPlayer with map_sides_id for each user+ladder combination
        // We use a subquery to find the latest qm_match_player.id per player_id+ladder_id
        $latestMatchPlayerIds = QmMatchPlayer::select('player_id', 'ladder_id', DB::raw('MAX(id) as latest_id'))
            ->whereNotNull('map_sides_id')
            ->when($ladderId, fn($q) => $q->where('ladder_id', $ladderId))
            ->when(!$includeAll, function($q) {
                // Filter to active users only (QM match in last 60 days)
                $cutoffDate = now()->subDays(60);
                $q->join('qm_matches', 'qm_match_players.qm_match_id', '=', 'qm_matches.id')
                  ->where('qm_matches.created_at', '>=', $cutoffDate);
            })
            ->groupBy('player_id', 'ladder_id');

        // Now fetch the actual QmMatchPlayer records for those IDs
        $query = QmMatchPlayer::whereIn('id', $latestMatchPlayerIds->pluck('latest_id'))
            ->with(['player.user', 'ladder', 'mapSides']);

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

        // Parse CSV (e.g., "0,5,-2,2" where -3=unset, -2=veto, -1=random, 0+=faction)
        $sideChoices = explode(',', $mapSidesValue);

        // Get ladder's current map pool's valid maps only, ordered by bit_idx
        // This ensures we only migrate picks for maps currently in the active pool
        $mapPool = $ladder->mapPool;
        if (!$mapPool) {
            $this->warn("Ladder {$ladder->id} has no map pool, skipping");
            return ['created' => 0, 'skipped' => 0];
        }

        $qmMaps = $mapPool->maps()
            ->where('valid', true)
            ->where('ladder_id', $ladder->id)
            ->orderBy('bit_idx')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($sideChoices as $bitIdx => $sideIndex) {
            // Find map with this bit_idx in the CURRENT POOL only
            $qmMap = $qmMaps->firstWhere('bit_idx', $bitIdx);

            if (!$qmMap) {
                // No map for this bit position in current pool, skip
                continue;
            }

            $sideIndex = (int) $sideIndex;

            // Validate side_index (encoding: -3=unset, -2=veto, -1=random, 0+=faction)
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

                // Create new pick (no encoding translation needed)
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
