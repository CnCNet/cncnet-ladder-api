<?php

use Illuminate\Database\Migrations\Migration;

class Orepurifierbaseplanningside extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "GAOREP.AIBasePlanningSide", "GAOREP", "AIBasePlanningSide")->save();
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
