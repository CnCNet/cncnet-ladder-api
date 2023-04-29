<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLadderRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qm_ladder_rules', function (Blueprint $table)
        {
            $table->text('ladder_rules_message');
            $table->string('ladder_discord'); //invitation url to the ladder discord
        });

        $ladder_rules = \App\QmLadderRules::all();

        foreach ($ladder_rules as $ladder_rule)
        {
            $ladder_rule->ladder_rules_message = "1. No pushing or intentionally feeding players points.\n2. Using multiple accounts to bypass the 1 nick restriction is not allowed.\n3. Disconnecting games is not allowed.\n4. Using a VPN to hide your identity is not allowed.\n5. No cancelling matches consecutively to block players from QMing.\n6. No abusing bugs on your opponents to gain an upper hand.\n7. Games that end in stalemate may be washed if photo/video is provided as proof of the stalemate.\n8. Games that need to be washed must be reported within 1 week. On the last 2 days of the month old games will not be washed.\n9. Opening menu and lowering the game speed during a game is not allowed.\n10. You are not allowed to impersonate other players\n11. You are not allowed to quit matches at start of match if you don't like the opponent/faction or whatever, this goes against competitive fair play. This is a competitive environment you should respect the game, your opponents, and the ladder or else don't play.\n12. If you are lagging games below 50fps you are subject to a cooldown.\n13. If a game is lagging you may report the game to be washed if you quit before 1min into the game. Fps should be below 50fps for it to be washed.";
            $ladder_rule->ladder_discord = "https://discord.com/invite/aJRJFe5";
            $ladder_rule->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qm_ladder_rules', function (Blueprint $table)
        {
            $table->dropColumn('ladder_rules_message');
            $table->dropColumn('ladder_discord');
        });
    }
}
