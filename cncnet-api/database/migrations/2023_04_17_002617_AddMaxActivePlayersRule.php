<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaxActivePlayersRule extends Migration
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
            $table->integer('max_active_players')->default(1); //max active players per user per ladder
        });

        $ladderRules = \App\QmLadderRules::all();

        foreach ($ladderRules as $ladderRule)
        {
            if ($ladderRule->ladder->abbreviation=='ts')
                $ladderRule->max_active_players = 3;
            $ladderRule->max_active_players = 1;
        }
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
