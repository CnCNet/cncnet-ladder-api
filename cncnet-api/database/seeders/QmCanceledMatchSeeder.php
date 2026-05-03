<?php

namespace Database\Seeders;

use App\Models\Ladder;
use App\Models\QmCanceledMatch;
use App\Models\QmMatch;
use App\Models\QmMap;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * docker exec dev_cncnet_ladder_app php artisan db:seed --class=QmCanceledMatchSeeder
 */
class QmCanceledMatchSeeder extends Seeder
{
    /**
     * Seed test data for canceled QM matches
     * Mix of player_canceled and failed_launch scenarios
     */
    public function run(): void
    {
        // Get Blitz 2v2 ladder
        $ladder = Ladder::where('abbreviation', 'blitz-2v2')->first();

        if (!$ladder) {
            $this->command->error('Blitz 2v2 ladder not found. Seeder requires ladder data.');
            return;
        }

        // Get a valid QM map from the ladder's map pool
        $qmMap = QmMap::where('ladder_id', $ladder->id)->first();

        // If no maps for this ladder, use any available QM map
        if (!$qmMap) {
            $this->command->warn("No QM maps found for {$ladder->name} ladder. Using first available map from any ladder.");
            $qmMap = QmMap::first();

            if (!$qmMap) {
                $this->command->error('No QM maps found in database at all. Cannot create test matches.');
                return;
            }
        }

        $this->command->info("Seeding canceled matches for ladder: {$ladder->name} (ID: {$ladder->id})");
        $this->command->info("Using map: {$qmMap->description} (ID: {$qmMap->id})");

        // Scenario 1: Player canceled - 1v1
        $match1 = QmMatch::create([
            'ladder_id' => $ladder->id,
            'qm_map_id' => $qmMap->id,
            'seed' => rand(100000, 999999),
        ]);
        QmCanceledMatch::create([
            'qm_match_id' => $match1->id,
            'player_id' => null,
            'ladder_id' => $ladder->id,
            'map_name' => 'Heck Freezes Over',
            'canceled_by_usernames' => 'PlayerOne',
            'affected_player_usernames' => 'PlayerTwo',
            'player_data' => [
                ['username' => 'PlayerOne', 'color' => 0],  // Yellow
                ['username' => 'PlayerTwo', 'color' => 4],  // Blue
            ],
            'reason' => 'player_canceled',
            'created_at' => Carbon::now()->subHours(2),
        ]);

        // Scenario 2: Failed launch - 1v1
        $match2 = QmMatch::create([
            'ladder_id' => $ladder->id,
            'qm_map_id' => $qmMap->id,
            'seed' => rand(100000, 999999),
        ]);
        QmCanceledMatch::create([
            'qm_match_id' => $match2->id,
            'player_id' => null,
            'ladder_id' => $ladder->id,
            'map_name' => 'Tour of Egypt',
            'canceled_by_usernames' => null,
            'affected_player_usernames' => 'AlphaGamer,BetaTester',
            'player_data' => [
                ['username' => 'AlphaGamer', 'color' => 2],  // Red
                ['username' => 'BetaTester', 'color' => 6],  // Green
            ],
            'reason' => 'failed_launch',
            'created_at' => Carbon::now()->subHours(5),
        ]);

        // Scenario 3: Player canceled - 2v2
        $match3 = QmMatch::create([
            'ladder_id' => $ladder->id,
            'qm_map_id' => $qmMap->id,
            'seed' => rand(100000, 999999),
        ]);
        QmCanceledMatch::create([
            'qm_match_id' => $match3->id,
            'player_id' => null,
            'ladder_id' => $ladder->id,
            'map_name' => 'Arena 33 Forever',
            'canceled_by_usernames' => 'QuitterPro',
            'affected_player_usernames' => 'TeamMate1,EnemyPlayer1,EnemyPlayer2',
            'player_data' => [
                ['username' => 'QuitterPro', 'color' => 0],    // Yellow
                ['username' => 'TeamMate1', 'color' => 1],     // Purple
                ['username' => 'EnemyPlayer1', 'color' => 2],  // Red
                ['username' => 'EnemyPlayer2', 'color' => 3],  // Orange
            ],
            'reason' => 'player_canceled',
            'created_at' => Carbon::now()->subHours(8),
        ]);

        // Scenario 4: Failed launch - 2v2
        $match4 = QmMatch::create([
            'ladder_id' => $ladder->id,
            'qm_map_id' => $qmMap->id,
            'seed' => rand(100000, 999999),
        ]);
        QmCanceledMatch::create([
            'qm_match_id' => $match4->id,
            'player_id' => null,
            'ladder_id' => $ladder->id,
            'map_name' => 'Cold Winter',
            'canceled_by_usernames' => null,
            'affected_player_usernames' => 'NorthTeam1,NorthTeam2,SouthTeam1,SouthTeam2',
            'player_data' => [
                ['username' => 'NorthTeam1', 'color' => 4],  // Blue
                ['username' => 'NorthTeam2', 'color' => 5],  // Pink
                ['username' => 'SouthTeam1', 'color' => 6],  // Green
                ['username' => 'SouthTeam2', 'color' => 7],  // Teal
            ],
            'reason' => 'failed_launch',
            'created_at' => Carbon::now()->subHours(12),
        ]);

        // Scenario 5: Recent player canceled - 1v1
        $match5 = QmMatch::create([
            'ladder_id' => $ladder->id,
            'qm_map_id' => $qmMap->id,
            'seed' => rand(100000, 999999),
        ]);
        QmCanceledMatch::create([
            'qm_match_id' => $match5->id,
            'player_id' => null,
            'ladder_id' => $ladder->id,
            'map_name' => 'Yalova',
            'canceled_by_usernames' => 'RageQuitter',
            'affected_player_usernames' => 'PatientPlayer',
            'player_data' => [
                ['username' => 'RageQuitter', 'color' => 2],    // Red
                ['username' => 'PatientPlayer', 'color' => 4],  // Blue
            ],
            'reason' => 'player_canceled',
            'created_at' => Carbon::now()->subMinutes(30),
        ]);

        // Scenario 6: Multiple cancels from same player - 1v1
        $match6 = QmMatch::create([
            'ladder_id' => $ladder->id,
            'qm_map_id' => $qmMap->id,
            'seed' => rand(100000, 999999),
        ]);
        QmCanceledMatch::create([
            'qm_match_id' => $match6->id,
            'player_id' => null,
            'ladder_id' => $ladder->id,
            'map_name' => 'Dry Heat',
            'canceled_by_usernames' => 'RageQuitter',
            'affected_player_usernames' => 'VictimPlayer',
            'player_data' => [
                ['username' => 'RageQuitter', 'color' => 0],   // Yellow
                ['username' => 'VictimPlayer', 'color' => 6],  // Green
            ],
            'reason' => 'player_canceled',
            'created_at' => Carbon::now()->subHours(1),
        ]);

        // Scenario 7: Very recent failed launch
        $match7 = QmMatch::create([
            'ladder_id' => $ladder->id,
            'qm_map_id' => $qmMap->id,
            'seed' => rand(100000, 999999),
        ]);
        QmCanceledMatch::create([
            'qm_match_id' => $match7->id,
            'player_id' => null,
            'ladder_id' => $ladder->id,
            'map_name' => 'Lake Placid',
            'canceled_by_usernames' => null,
            'affected_player_usernames' => 'UnluckyGamer1,UnluckyGamer2',
            'player_data' => [
                ['username' => 'UnluckyGamer1', 'color' => 1],  // Purple
                ['username' => 'UnluckyGamer2', 'color' => 3],  // Orange
            ],
            'reason' => 'failed_launch',
            'created_at' => Carbon::now()->subMinutes(10),
        ]);

        // Scenario 8: 2v2 with multiple cancelers (both teammates quit)
        $match8 = QmMatch::create([
            'ladder_id' => $ladder->id,
            'qm_map_id' => $qmMap->id,
            'seed' => rand(100000, 999999),
        ]);
        QmCanceledMatch::create([
            'qm_match_id' => $match8->id,
            'player_id' => null,
            'ladder_id' => $ladder->id,
            'map_name' => 'Meat Grinder',
            'canceled_by_usernames' => 'CowardA,CowardB',
            'affected_player_usernames' => 'BraveOne,BraveTwo',
            'player_data' => [
                ['username' => 'CowardA', 'color' => 0],   // Yellow
                ['username' => 'CowardB', 'color' => 1],   // Purple
                ['username' => 'BraveOne', 'color' => 2],  // Red
                ['username' => 'BraveTwo', 'color' => 4],  // Blue
            ],
            'reason' => 'player_canceled',
            'created_at' => Carbon::now()->subHours(3),
        ]);

        $this->command->info('Successfully seeded 8 canceled match scenarios');
        $this->command->info('- 5 player_canceled scenarios');
        $this->command->info('- 3 failed_launch scenarios');
        $this->command->info('- Mix of 1v1 and 2v2 matches');
        $this->command->info('');
        $this->command->info('To clean up test data:');
        $this->command->info("DELETE FROM qm_canceled_matches WHERE qm_match_id IN ({$match1->id}, {$match2->id}, {$match3->id}, {$match4->id}, {$match5->id}, {$match6->id}, {$match7->id}, {$match8->id});");
        $this->command->info("DELETE FROM qm_matches WHERE id IN ({$match1->id}, {$match2->id}, {$match3->id}, {$match4->id}, {$match5->id}, {$match6->id}, {$match7->id}, {$match8->id});");
    }
}
