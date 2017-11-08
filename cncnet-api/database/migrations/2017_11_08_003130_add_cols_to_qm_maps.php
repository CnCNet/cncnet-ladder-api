<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColsToQmMaps extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qm_maps', function(Blueprint $table)
        {
            //
            $table->string("allowed_sides");
        });

        $ladders = \App\Ladder::all();
        foreach ($ladders as $ladder)
        {
            $qmLadderRules = $ladder->qmLadderRules()->first();
            foreach ($ladder->qmMaps as $qmMap)
            {
                $qmMap->allowed_sides = $qmLadderRules->allowed_sides;
                $qmMap->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qm_maps', function(Blueprint $table)
        {
            //
            $table->dropColumn("allowed_sides");
        });
    }
}
