<?php

use Illuminate\Database\Schema\Blueprint;
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
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWNMAP_INI, "GAWEAP.BuildTimeMultiplier", "E1", "BuildTimeMultiplier")->save();
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWNMAP_INI, "NAWEAP.BuildTimeMultiplier", "E1", "BuildTimeMultiplier")->save();
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWNMAP_INI, "GAOREP.UnitsCostBonus", "GAOREP", "UnitsCostBonus")->save();
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
