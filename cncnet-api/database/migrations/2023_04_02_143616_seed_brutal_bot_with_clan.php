<?php

use App\AIPlayer;
use App\Clan;
use App\ClanPlayer;
use App\LadderHistory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SeedBrutalBotWithClan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $brutalClan = new Clan();
        $brutalClan->ladder_id = 9;
        $brutalClan->short = "brutal";
        $brutalClan->name = "Brutal AI Clan";
        $brutalClan->save();

        $history = LadderHistory::find(744);
        $player = AIPlayer::getAIPlayer($history);
        $clanPlayer = new ClanPlayer();
        $clanPlayer->player_id = $player->id;
        $clanPlayer->clan_id = $brutalClan->id;
        $clanPlayer->clan_role_id = 1;
        $clanPlayer->save();
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
