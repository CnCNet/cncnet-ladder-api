<?php

use Illuminate\Database\Migrations\Migration;

class AddGiSpawnIniValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "E1.BuildTimeMultiplier", "E1", "BuildTimeMultiplier")->save();
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
