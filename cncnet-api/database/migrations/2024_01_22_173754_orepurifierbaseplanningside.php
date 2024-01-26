<?php

use Illuminate\Database\Schema\Blueprint;
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
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWNMAP_INI, "GAOREP.AIBasePlanningSide", "GAOREP", "AIBasePlanningSide")->save();
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
