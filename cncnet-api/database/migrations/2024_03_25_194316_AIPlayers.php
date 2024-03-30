<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\SpawnOption;
use App\Models\SpawnOptionType;

class AIPlayers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Settings.AIPlayers", "Settings", "AIPlayers")->save(); // 0
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
