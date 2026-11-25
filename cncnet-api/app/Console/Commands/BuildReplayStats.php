<?php

namespace App\Console\Commands;

use App\Http\Services\ChatFlagService;
use App\Http\Services\ReplayService;
use App\Models\GameReplay;
use App\Models\GameReplaySeals;
use Illuminate\Console\Command;

class BuildReplayStats extends Command
{
    protected $signature = 'replays:build-stats {--game= : Only this game} {--force : Rebuild stats, seals and chat that are already current}';

    protected $description = 'Read canonical seals and chat out of stored replays, and statistics from each game\'s best replay';

    public function handle()
    {
        $service = new ReplayService();
        $force = (bool) $this->option('force');

        $query = GameReplay::query();
        if ($this->option('game'))
        {
            $query->where('game_id', (int) $this->option('game'));
        }

        // Every copy's seals and chat first. The seals say how far each copy reached, which picks the
        // one the game's statistics are read from.
        $sealed = 0;
        $chats = 0;
        (clone $query)->chunkById(100, function ($replays) use ($service, $force, &$sealed, &$chats)
        {
            foreach ($replays as $replay)
            {
                $hasSeals = GameReplaySeals::where('game_id', $replay->game_id)
                    ->where('player_id', $replay->player_id)->exists();
                if (($force || !$hasSeals) && $service->storeSeals($replay))
                {
                    $sealed++;
                }

                if (($force || $service->needsChat($replay)) && $service->storeChat($replay))
                {
                    $chats++;
                }
            }
        });

        $built = 0;
        $flags = new ChatFlagService();
        foreach ((clone $query)->distinct()->orderBy('game_id')->pluck('game_id') as $gameId)
        {
            if ($service->refreshStats((int) $gameId, $force))
            {
                $built++;
            }
            $flags->refresh((int) $gameId);
        }

        $this->info("Read seals from {$sealed} replays and chat from {$chats}, and built stats for {$built} games.");
        return 0;
    }
}
