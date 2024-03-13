<?php

use Illuminate\Database\Migrations\Migration;

class Grandcannon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "GTGCAN.BuildLimit", "GTGCAN", "BuildLimit")->save();
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "GTGCAN.TechLevel", "GTGCAN", "TechLevel")->save();
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "GTGCAN.RequiredHouses", "GTGCAN", "RequiredHouses")->save();
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
