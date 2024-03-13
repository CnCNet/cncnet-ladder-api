<?php

use App\Models\SpawnOption;
use App\Models\SpawnOptionType;
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
