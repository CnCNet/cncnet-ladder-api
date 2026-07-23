<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\Ladder;
use App\Models\Player;
use App\Models\PlayerGameReport;

class ObserverBlitzGameSeeder extends Seeder
{
    /**
     * Seed observer into recent Blitz 1v1 game for testing observer UI display.
     *
     * Run with: php artisan db:seed --class=ObserverBlitzGameSeeder
     */
    public function run(): void
    {
        $this->command->info('=== Adding Observer to Recent Blitz Game ===');

        // Find blitz ladder
        $ladder = Ladder::where('abbreviation', 'blitz')->first();

        if (!$ladder) {
            $this->command->error('Blitz ladder not found');
            return;
        }

        // Find recent blitz game without observer
        $game = Game::whereNotNull('game_report_id')
            ->whereHas('ladderHistory', function($q) use ($ladder) {
                $q->where('ladder_id', $ladder->id);
            })
            ->whereHas('report.playerGameReports', function($q) {
                $q->where('spectator', 0);
            })
            ->with(['ladderHistory.ladder', 'report.playerGameReports.player'])
            ->orderByDesc('id')
            ->first();

        if (!$game) {
            $this->command->error('No suitable Blitz game found');
            return;
        }

        $this->command->info("Found game: {$game->id}");
        $this->command->info("Ladder: blitz");

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
        $this->command->info("View at: http://localhost:3000/ladder/{$game->ladderHistory->short}/blitz/games/{$game->id}");
        $this->command->info("Ladder page: http://localhost:3000/ladder/{$game->ladderHistory->short}/blitz");
        $this->command->newLine();
        $this->command->warn("To remove observer, run: php artisan db:seed --class=CleanObserverGameSeeder");
    }
}
