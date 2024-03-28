<?php

namespace Tests\Feature\Api\Qm\MatchRequest;

use App\Models\Clan;
use App\Models\GameObjectSchema;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Map;
use App\Models\MapPool;
use App\Models\Player;
use App\Models\PlayerHistory;
use App\Models\QmLadderRules;
use App\Models\QmMap;
use App\Models\Side;
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

    protected function makeLadder($playerCount = 2, $team = false, $clan = false) {

        $ladder = Ladder::factory()->create([
            'name' => 'Test ladder',
            'clans_allowed' => $clan,
            'abbreviation' => 'tl',
        ]);

        Side::factory()->count(3)
            ->sequence(fn ($sequence) => ['local_id' => $sequence->index])
                ->create([
                'ladder_id' => $ladder->id,
            ]);
        $qmlr = QmLadderRules::factory()->create([
            'ladder_id' => $ladder->id,
            'player_count' => $playerCount,
            'allowed_sides' => '-1,' . join(',', $ladder->sides->pluck('local_id')->toArray()),
        ]);

        $mpool = MapPool::factory()->create([
            'ladder_id' => $ladder->id,
        ]);

        // set current map_pool
        $ladder->update([
            'map_pool_id' => $mpool->id,
        ]);

        $maps = Map::factory()
            ->count(4)
            ->create([
                'spawn_count' => $playerCount,
                'ladder_id' => $ladder->id,
            ]);

        foreach($maps as $index => $map) {
            $m = QmMap::factory()->create([
                'ladder_id' => $ladder->id,
                'map_pool_id' => $mpool->id,
                'map_id' => $map->id,
                'description' => $map->name,
                'valid' => 1,
                'bit_idx' => $index,
                'spawn_order' => join(',', array_fill(0, $playerCount, 0)),
                'allowed_sides' => $qmlr->allowed_sides,
            ]);
            if($team || $clan) {
                $m->team1_spawn_order = join(',', range(1, $playerCount/2));
                $m->team2_spawn_order = join(',', range($playerCount/2 + 1, $playerCount));
                $m->save();
            }
        }

        return $ladder;
    }

    protected function makeLadderHistory(Ladder $ladder) {
        return LadderHistory::factory()->create([
            'ladder_id' => $ladder->id,
            'starts' => now()->startOfMonth(),
            'ends' => now()->endOfMonth(),
        ]);
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

    protected function makeClan($name, Ladder $ladder) {
        return Clan::factory()->create([
            'ladder_id' => $ladder->id,
        ]);
    }
}