<?php

use Illuminate\Database\Migrations\Migration;

class Orepurifier extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "GAOREP.TechLevel", "GAOREP", "TechLevel")->save();
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
