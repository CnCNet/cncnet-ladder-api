<?php

use App\Models\SpawnOption;
use App\Models\SpawnOptionType;
use Illuminate\Database\Migrations\Migration;

class AddProtocolVersionSpawnOption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Protocol", "Settings", "Protocol")->save();
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
