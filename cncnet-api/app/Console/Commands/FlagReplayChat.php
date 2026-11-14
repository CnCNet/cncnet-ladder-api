<?php

namespace App\Console\Commands;

use App\Http\Services\ChatFlagService;
use Illuminate\Console\Command;

class FlagReplayChat extends Command
{
    protected $signature = 'replays:flag-chat {--game= : Only this game}';

    protected $description = 'Re-read the chat flags of games whose chat has been read from their replays';

    public function handle()
    {
        $service = new ChatFlagService();

        if ($this->option('game'))
        {
            $count = $service->refresh((int) $this->option('game'));
            $this->info("Game {$this->option('game')} has {$count} chat flags.");
            return 0;
        }

        $this->info($service->refreshAll() . ' games have chat flags.');
        return 0;
    }
}
