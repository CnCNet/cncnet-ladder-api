<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AIPlayers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\SpawnOption::makeOne(\App\SpawnOptionType::SPAWN_INI, "Settings.AIPlayers", "Settings", "AIPlayers")->save(); // 0
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
