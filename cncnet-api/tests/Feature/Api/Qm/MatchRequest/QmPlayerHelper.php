<?php

namespace Tests\Feature\Api\Qm\MatchRequest;

use App\Models\GameObjectSchema;
use App\Models\Ladder;
use App\Models\LadderHistory;
use App\Models\Player;
use App\Models\PlayerHistory;
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

    protected function makeLadder() {

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

        return $ladder;
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