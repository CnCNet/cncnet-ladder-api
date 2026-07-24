<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlayerGameReport;

class CleanObserverGameSeeder extends Seeder
{
    /**
     * Remove all observer PlayerGameReports created today (for testing cleanup).
     *
     * Run with: php artisan db:seed --class=CleanObserverGameSeeder
     */
    public function run(): void
    {
        $this->command->info('=== Cleaning Test Observers ===');

        // Find observers created today
        $observers = PlayerGameReport::where('spectator', 1)
            ->whereDate('created_at', today())
            ->get();

        if ($observers->isEmpty()) {
            $this->command->info('No observers created today found');
            return;
        }

        $this->command->info("Found {$observers->count()} observer(s) created today:");

        foreach ($observers as $obs) {
            $gameName = $obs->game->id ?? 'N/A';
            $playerName = $obs->player->username ?? 'Unknown';
            $this->command->line("  - Game {$gameName}: {$playerName} (PGR ID: {$obs->id})");
        }

        $count = PlayerGameReport::where('spectator', 1)
            ->whereDate('created_at', today())
            ->delete();

        $this->command->newLine();
        $this->command->info("Deleted {$count} observer record(s)");
    }
}
