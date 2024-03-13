<?php

use Illuminate\Database\Migrations\Migration;

class Orepurifierfactory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "GAOREP.FactoryPlant", "GAOREP", "FactoryPlant")->save();
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
