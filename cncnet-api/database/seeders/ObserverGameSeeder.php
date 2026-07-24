<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerGameReport;

class ObserverGameSeeder extends Seeder
{
    /**
     * Seed observer into recent game for testing observer UI display.
     *
     * Run with: php artisan db:seed --class=ObserverGameSeeder
     */
    public function run(): void
    {
        $this->command->info('=== Adding Observer to Recent Game ===');

        // Find recent game without observer
        $game = Game::whereNotNull('game_report_id')
            ->whereNotNull('ladder_history_id')
            ->whereHas('report.playerGameReports', function($q) {
                $q->where('spectator', 0);
            })
            ->with(['ladderHistory.ladder', 'report.playerGameReports.player'])
            ->orderByDesc('id')
            ->first();

        if (!$game) {
            $this->command->error('No suitable game found');
            return;
        }

        if (!$game->ladderHistory || !$game->ladderHistory->ladder) {
            $this->command->error("Game {$game->id} has no ladder");
            return;
        }

        $ladder = $game->ladderHistory->ladder;

        $this->command->info("Found game: {$game->id}");
        $this->command->info("Ladder: {$ladder->abbreviation}");

        $existingPlayers = $game->report->playerGameReports;
        $this->command->info("Current players:");
        foreach ($existingPlayers as $pgr) {
            $this->command->line("  - {$pgr->player->username} (spectator: {$pgr->spectator})");
        }

        // Get a random player to be fake observer (not already in game)
        $observerPlayer = Player::where('ladder_id', $ladder->id)
            ->whereNotIn('id', $existingPlayers->pluck('player_id'))
            ->inRandomOrder()
            ->first();

        if (!$observerPlayer) {
            $this->command->error('No available player for observer');
            return;
        }

        $this->command->info("\nAdding observer: {$observerPlayer->username}");

        // Create observer PlayerGameReport
        $pgr = new PlayerGameReport();
        $pgr->game_id = $game->id;
        $pgr->game_report_id = $game->game_report_id;
        $pgr->player_id = $observerPlayer->id;
        $pgr->spectator = 1;
        $pgr->points = 0;
        $pgr->won = false;
        $pgr->defeated = false;
        $pgr->draw = false;
        $pgr->save();

        $this->command->info("Created observer PlayerGameReport ID: {$pgr->id}");

        $this->command->newLine();
        $this->command->info('=== SUCCESS ===');
        $this->command->info("Game ID: {$game->id}");
        $this->command->info("View at: http://localhost:3000/ladder/{$game->ladderHistory->short}/{$ladder->abbreviation}/games/{$game->id}");
        $this->command->info("Ladder page: http://localhost:3000/ladder/{$game->ladderHistory->short}/{$ladder->abbreviation}");
        $this->command->newLine();
        $this->command->warn("To remove observer, run: php artisan db:seed --class=CleanObserverGameSeeder");
    }
}
