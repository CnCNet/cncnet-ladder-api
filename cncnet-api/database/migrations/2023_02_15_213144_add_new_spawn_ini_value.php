<?php

use App\SpawnOption;
use App\SpawnOptionType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewSpawnIniValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "MaxLatencyLevel", "Settings", "MaxLatencyLevel")->save();
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
