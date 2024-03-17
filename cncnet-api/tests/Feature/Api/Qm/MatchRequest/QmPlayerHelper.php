<?php

namespace Tests\Feature\Api\Qm\MatchRequest;

use App\Models\GameObjectSchema;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Map;
use App\Models\MapPool;
use App\Models\Player;
use App\Models\PlayerHistory;
use App\Models\QmLadderRules;
use App\Models\QmMap;
use App\Models\User;
use App\Models\UserSettings;
use Illuminate\Support\Facades\Hash;

trait QmPlayerHelper
{

    protected function makeUser($name) {
        $user = User::create([
            'name' => $name,
            'email' => $name.'@test.com',
            'password' => Hash::make($name),
            'email_verified' => 1
        ]);

        UserSettings::create([
            'user_id' => $user->id,
        ]);

        return $user;
    }

    protected function makeLadder($playerCount = 2) {

        $gos = GameObjectSchema::create([
            'name' => 'Yuri\'s Revenge Schema',
        ]);


        $ladder = Ladder::create([
            'name' => 'Test ladder',
            'abbreviation' => 'tl',
            'game' => 'yr',
            'clans_allowed' => false,
            'game_object_schema_id' => $gos->id,
            'private' => false,
        ]);

        $qmlr = QmLadderRules::create([
            'ladder_id' => $ladder->id,
            'player_count' => $playerCount,
            'map_vetoes' => 0,
            'max_difference' => 498,
            'all_sides' => '0,1,2,3,4,5,6,7,8,9',
            'allowed_sides' => '-1,0,1,2,3,4,5,6,7,8,9',
            'bail_time' => 0,
            'bail_fps' => 0,
            'tier2_rating' => 1200,
            'rating_per_second' => 0.75,
            'max_points_difference' => 400,
            'points_per_second' => 1,
            'use_elo_points' => 1,
            'wol_k' => 64,
            'show_map_preview' => 1,
            'reduce_map_repeats' => 5,
            'use_ranked_map_picker' => 0,
        ]);

        $mpool = MapPool::create([
            'name' => 'Pool',
            'ladder_id' => $ladder->id,
        ]);

        // wtf is this ahah
        $ladder->update([
            'map_pool_id' => $mpool->id,
        ]);


        $maps = [];
        $maps[] = $this->makeMap('m1', $ladder, $playerCount);
        $maps[] = $this->makeMap('m2', $ladder, $playerCount);
        $maps[] = $this->makeMap('m3', $ladder, $playerCount);
        $maps[] = $this->makeMap('m4', $ladder, $playerCount);

        foreach($maps as $map) {
            $this->makeQmMap($map, $ladder, $mpool);
        }


        return $ladder;
    }

    protected function makeMap($name, Ladder $ladder, $playerCount) {
        return Map::create([
            'name' => $name,
            'hash' => Hash::make($name),
            'spawn_count' => $playerCount,
            'ladder_id' => $ladder->id,
        ]);
    }

    protected function makeQmMap(Map $map, Ladder $ladder, MapPool $pool) {
        return QmMap::create([
            'ladder_id' => $ladder->id,
            'map_pool_id' => $pool->id,
            'map_id' => $map->id,
            'valid' => 1,
            'description' => $map->name,
        ]);
    }

    protected function makeLadderHistory(Ladder $ladder) {
        $lh = new LadderHistory([
            'ladder_id' => $ladder->id,
            'starts' => today()->startOfMonth(),
            'ends' => today()->endOfMonth(),
            'short' => today()->startOfMonth()->format('n-Y')
        ]);
        $lh->save();
        return $lh;
    }

    protected function makePlayerForLadder($playername, Ladder $ladder, User $user) {
        return Player::create([
            'user_id' => $user->id,
            'username' => $playername,
            'ladder_id' => $ladder->id,
        ]);
    }

    protected function makePlayerHistory(Player $player, LadderHistory $lh) {
        return PlayerHistory::create([
            'player_id' => $player->id,
            'ladder_history_id' => $lh->id,
            'tier' => 1
        ]);
    }
}