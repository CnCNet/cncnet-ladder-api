<?php

use App\Models\PlayerHistory;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UserTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_tiers', function (Blueprint $table)
        {
            $table->increments("id");
            $table->unsignedInteger("user_id");
            $table->integer("ladder_id");
            $table->integer("tier");
            $table->boolean("both_tiers")->default(false);
            $table->timestamps();
        });

        $now = Carbon::now();
        $start = $now->startOfMonth()->toDateTimeString();
        $end = $now->endOfMonth()->toDateTimeString();

        $ladderHistories = \App\Models\LadderHistory::whereBetween("starts", [$start, $start])
            ->whereBetween("ends", [$end, $end])
            ->get();

        foreach ($ladderHistories as $ladderHistory)
        {
            $usersThisMonth = PlayerHistory::where("ladder_history_id", $ladderHistory->id)
                ->join("players as p", "p.id", "=", "player_histories.player_id")
                ->join("users as u", "u.id", "=", "p.user_id")
                ->select(["u.*", "p.id as player_id"])
                ->get();

            foreach ($usersThisMonth as $user)
            {
                $player = \App\Models\Player::find($user->player_id);

                // Migrates current data 
                $cachedTier = $player->getCachedPlayerTierByLadderHistory($ladderHistory);
                $bothTiers = $this->checkLegacyLeaguePlayer($user, $ladderHistory->ladder);

                $userTier = new \App\Models\UserTier();
                $userTier->user_id = $user->id;
                $userTier->ladder_id = $ladderHistory->ladder->id;
                $userTier->tier = $cachedTier ?? 1;
                $userTier->both_tiers = $bothTiers;
                $userTier->save();
            }
        }
    }

    private function checkLegacyLeaguePlayer($user, $ladder)
    {
        $leaguePlayer = \App\Models\LeaguePlayer::where("user_id", $user->id)->where("ladder_id", $ladder->id)->first();
        if ($leaguePlayer)
        {
            return $leaguePlayer->can_play_both_tiers;
        }
        return false;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
