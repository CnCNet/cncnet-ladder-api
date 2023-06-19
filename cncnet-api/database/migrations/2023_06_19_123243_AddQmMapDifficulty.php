<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQmMapDifficulty extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qm_maps', function (Blueprint $table)
        {
            $table->text('difficulty')->unsignedInteger()->default(1);
        });

        Schema::table('qm_ladder_rules', function (Blueprint $table)
        {
            $table->text('use_ranked_map_picker')->boolean()->default(false);
        });
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
