<?php

namespace Database\Seeders;

use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Map;
use App\Models\MapPool;
use App\Models\Player;
use App\Models\PlayerActiveHandle;
use App\Models\QmLadderRules;
use App\Models\QmMap;
use App\Models\Side;
use App\Models\User;
use App\Models\UserSettings;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Sets up a local-only YR ladder with replays enabled, so the Quick Match replay flow can be
 * exercised end to end without touching production.
 *
 * The maps are referenced by the SHA1 of real .map files already present in the game's
 * "Maps/Yuri's Revenge" folder. The Quick Match client matches maps by hashing local files, so
 * using real hashes means it finds them immediately instead of trying to download them from
 * ladder.cncnet.org.
 *
 * Idempotent - safe to run repeatedly.
 *
 *   docker exec dev_cncnet_ladder_app php artisan db:seed --class=QmReplayTestSeeder
 */
class QmReplayTestSeeder extends Seeder
{
    // Must match a folder under resources/images/games/, otherwise the ladder listing page fails
    // to resolve its logo through the Vite manifest and 500s.
    private const LADDER_ABBREVIATION = 'yr';
    private const TEST_PASSWORD = 'qmtest1234';

    /**
     * SHA1 of real map files shipped in the game folder. If these are ever regenerated, run
     * `sha1sum` against "Maps/Yuri's Revenge/<file>" and update them here.
     */
    private const MAPS = [
        ['hash' => '642bb8c8f8d6a15879d3afe276a54e4e4881b717', 'name' => 'Arctic Circle', 'file' => '2_arctic_circle.map'],
        ['hash' => '44d1ac0d28d3c9c0be36b14516aa61c54e97fcf7', 'name' => 'Cold War',      'file' => '2_cold_war.map'],
        ['hash' => 'aee78cb0db9e784d21bc5cfc07c4f144ec54f5b9', 'name' => 'Blockade',      'file' => '2_blockade.map'],
    ];

    public function run(): void
    {
        $ladder = Ladder::updateOrCreate(
            ['abbreviation' => self::LADDER_ABBREVIATION],
            [
                'name'                  => 'QM Replay Test',
                'game'                  => 'yr',
                'clans_allowed'         => false,
                'private'               => false,
                'order'                 => 99,
                'ladder_type'           => '1vs1',
                'game_object_schema_id' => 1,
            ]
        );

        $this->command->info("Ladder #{$ladder->id} ({$ladder->abbreviation})");

        // Ladder history for the current month - this is what the /ladder/<short>/<abbrev> URLs use.
        $now = Carbon::now();
        $history = LadderHistory::updateOrCreate(
            [
                'ladder_id' => $ladder->id,
                'short'     => $now->month . '-' . $now->year,
            ],
            [
                'starts' => $now->copy()->startOfMonth(),
                'ends'   => $now->copy()->endOfMonth(),
            ]
        );

        $this->command->info("History #{$history->id} ({$history->short})");

        foreach (['Allied' => 0, 'Soviet' => 1, 'Yuri' => 2] as $name => $localId)
        {
            Side::updateOrCreate(
                ['ladder_id' => $ladder->id, 'local_id' => $localId],
                ['name' => $name]
            );
        }

        // -1 is "random side".
        $allowedSides = '-1,0,1,2';

        // The point of the whole exercise: replays on for this ladder only.
        $rules = QmLadderRules::updateOrCreate(
            ['ladder_id' => $ladder->id],
            [
                'player_count'             => 2,
                'map_vetoes'               => 1,
                'max_difference'           => 1000,
                'all_sides'                => $allowedSides,
                'allowed_sides'            => $allowedSides,
                'bail_time'                => 30,
                'bail_fps'                 => 30,
                'tier2_rating'             => 0,
                'rating_per_second'        => 0.75,
                'max_points_difference'    => 1000,
                'points_per_second'        => 0.5,
                'use_elo_points'           => true,
                'wol_k'                    => 64,
                'upset_k'                  => 20,
                'upset_k_loser_multiplier' => 0.25,
                'fixed_points'             => 12,
                'no_negative_points'       => true,
                'show_map_preview'         => true,
                'reduce_map_repeats'       => 0,
                'use_ranked_map_picker'    => false,
                'enable_replays'           => true,
            ]
        );

        $this->command->info("Rules #{$rules->id} enable_replays=" . ($rules->enable_replays ? 'YES' : 'no'));

        $mapPool = MapPool::updateOrCreate(
            ['ladder_id' => $ladder->id, 'name' => 'QM Replay Test Pool'],
            []
        );

        $ladder->update(['map_pool_id' => $mapPool->id]);

        foreach (self::MAPS as $index => $mapData)
        {
            $map = Map::updateOrCreate(
                ['hash' => $mapData['hash'], 'ladder_id' => $ladder->id],
                [
                    'name'        => $mapData['name'],
                    'spawn_count' => 2,
                    'image_path'  => '',
                    'image_hash'  => '',
                    'filename'    => $mapData['file'],
                    'is_active'   => 1,
                ]
            );

            QmMap::updateOrCreate(
                ['ladder_id' => $ladder->id, 'map_id' => $map->id, 'map_pool_id' => $mapPool->id],
                [
                    'description'       => $mapData['name'],
                    'admin_description' => $mapData['name'],
                    'bit_idx'           => $index,
                    'valid'             => 1,
                    'spawn_order'       => '0,0',
                    'team1_spawn_order' => '',
                    'team2_spawn_order' => '',
                    'allowed_sides'     => $allowedSides,
                    'rejectable'        => 1,
                    'default_reject'    => 0,
                    'random_spawns'     => 0,
                    'map_tier'          => 1,
                    'weight'            => 1,
                ]
            );

            $this->command->info("  Map {$mapData['name']} ({$mapData['file']})");
        }

        // Two accounts so a real 1v1 can be played across two machines. Both are given the God
        // group so the staff-only replay download buttons are visible without extra setup.
        foreach (['qmtest1', 'qmtest2'] as $username)
        {
            $user = User::updateOrCreate(
                ['email' => $username . '@example.test'],
                [
                    'name'           => $username,
                    'password'       => Hash::make(self::TEST_PASSWORD),
                    'group'          => 'God',
                    'email_verified' => 1,
                    'alias'          => '',
                    'chat_allowed'   => 1,
                ]
            );

            UserSettings::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'match_ai'          => 0,
                    'match_any_map'     => 1,
                    'allow_observers'   => 1,
                    'skip_score_screen' => 0,
                ]
            );

            $player = Player::updateOrCreate(
                ['username' => $username, 'ladder_id' => $ladder->id],
                ['user_id' => $user->id, 'card_id' => 0, 'is_bot' => 0]
            );

            // The client lists usernames from player_active_handles, not from players directly,
            // and only counts handles created within the current month. Without this the account
            // endpoint returns an empty list and the client has nothing to queue with.
            PlayerActiveHandle::updateOrCreate(
                ['user_id' => $user->id, 'player_id' => $player->id, 'ladder_id' => $ladder->id],
                ['created_at' => Carbon::now()]
            );

            $this->command->info("  User {$username} / " . self::TEST_PASSWORD . " (group God)");
        }

        $this->command->info('');
        $this->command->info('Seed complete. Point the Quick Match client at this API and log in as qmtest1 / qmtest2.');
    }
}
