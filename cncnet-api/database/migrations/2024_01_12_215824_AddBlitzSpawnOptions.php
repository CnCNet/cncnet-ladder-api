<?php

use Illuminate\Database\Migrations\Migration;

class AddBlitzSpawnOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "GAWEAP.BuildTimeMultiplier", "GAWEAP", "BuildTimeMultiplier")->save();
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "NAWEAP.BuildTimeMultiplier", "NAWEAP", "BuildTimeMultiplier")->save();
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "GAOREP.UnitsCostBonus", "GAOREP", "UnitsCostBonus")->save();
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
