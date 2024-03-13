<?php

use App\Models\SpawnOption;
use App\Models\SpawnOptionType;
use Illuminate\Database\Migrations\Migration;

class AddSkipScoreScreenSpawnOption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "SkipScoreScreen", "Settings", "SkipScoreScreen")->save();
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
