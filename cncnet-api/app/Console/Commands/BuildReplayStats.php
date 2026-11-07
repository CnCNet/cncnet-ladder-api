<?php

namespace App\Console\Commands;

use App\Http\Services\ReplayService;
use App\Models\GameReplay;
use App\Models\GameReplaySeals;
use Illuminate\Console\Command;

class BuildReplayStats extends Command
{
    protected $signature = 'replays:build-stats {--game= : Only this game} {--force : Rebuild stats and seals that are already current}';

    protected $description = 'Read statistics and canonical seals out of stored replays, for games without current ones';

    public function handle()
    {
        $service = new ReplayService();
        $force = (bool) $this->option('force');

        $query = GameReplay::orderBy('game_id')->orderBy('id');
        if ($this->option('game'))
        {
            $query->where('game_id', (int) $this->option('game'));
        }

        $built = 0;
        $sealed = 0;
        $skipped = 0;
        $done = [];

        $query->chunkById(100, function ($replays) use ($service, $force, &$built, &$sealed, &$skipped, &$done)
        {
            foreach ($replays as $replay)
            {
                // Every copy has seals of its own.
                $hasSeals = GameReplaySeals::where('game_id', $replay->game_id)
                    ->where('player_id', $replay->player_id)->exists();
                if (($force || !$hasSeals) && $service->storeSeals($replay))
                {
                    $sealed++;
                }

                // One replay per game is enough for the statistics; try the next one only if the
                // first had none.
                if (isset($done[$replay->game_id]) || (!$force && !$service->needsStats($replay->game_id)))
                {
                    continue;
                }

                if ($service->buildStats($replay))
                {
                    $done[$replay->game_id] = true;
                    $built++;
                }
                else
                {
                    $skipped++;
                }
            }
        });

        $this->info("Built stats for {$built} games and read seals from {$sealed} replays; {$skipped} replays had no stats to give.");
        return 0;
    }
}
