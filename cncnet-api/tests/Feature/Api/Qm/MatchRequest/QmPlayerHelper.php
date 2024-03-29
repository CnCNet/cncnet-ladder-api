<?php

namespace Tests\Feature\Api\Qm\MatchRequest;

use App\Models\Clan;
use App\Models\ClanPlayer;
use App\Models\ClanRole;
use App\Models\Game;
use App\Models\GameObjectSchema;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Map;
use App\Models\MapPool;
use App\Models\Player;
use App\Models\PlayerHistory;
use App\Models\QmLadderRules;
use App\Models\QmMap;
use App\Models\QmMatch;
use App\Models\QmMatchPlayer;
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
                'filename' => fake()->word . '.map'
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

    protected function makeQmMatch(Ladder $ladder, QmMap $qmMap): QmMatch  {
        $qmMatch = QmMatch::factory()->create([
            'ladder_id' => $ladder->id,
            'qm_map_id' => $qmMap->id,
            'seed' => mt_rand(-2147483647, 2147483647),
            'tier' => 1,
        ]);
        # Create the Game
        $game = Game::genQmEntry($qmMatch, Game::GAME_TYPE_1VS1);
        $qmMatch->game_id = $game->id;
        $qmMatch->save();
        $game->qm_match_id = $qmMatch->id;
        $game->save();

        return $qmMatch;
    }

    protected function makeQmMatchPlayer(Player $player, Ladder $ladder, ?QmMatch $qmMatch = null, ?array $attributes = null): QmMatchPlayer {
        return QmMatchPlayer::create(array_merge([
            'player_id' => $player->id,
            'ladder_id' => $ladder->id,
            'tier' => 1,
            'qm_match_id' => $qmMatch?->id ?? null
        ], $attributes ?? []));
    }

    protected function makePlayerHistory(Player $player, LadderHistory $lh) {
        return PlayerHistory::create([
            'player_id' => $player->id,
            'ladder_history_id' => $lh->id,
            'tier' => 1
        ]);
    }

    protected function createClanRoles() {
        ClanRole::create([
            'id' => 1,
            'value' => 'Owner'
        ]);
        ClanRole::create([
            'id' => 2,
            'value' => 'Manager'
        ]);
        ClanRole::create([
            'id' => 3,
            'value' => 'Member'
        ]);
    }

    protected function makeClan($name, Ladder $ladder) {
        return Clan::factory()->create([
            'ladder_id' => $ladder->id,
        ]);
    }

    protected function addPlayersInClan(Clan $clan, array $players) {
        foreach($players as $i => $player) {
            ClanPlayer::create([
                'clan_id' => $clan->id,
                'player_id' => $player->id,
                'clan_role_id' => $i == 0 ? 1 : 3
            ]);
        }
    }
}