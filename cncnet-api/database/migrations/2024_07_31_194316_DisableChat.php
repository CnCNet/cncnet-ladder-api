<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\SpawnOption;
use App\Models\SpawnOptionType;

class DisableChat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Settings.DisableChat", "Settings", "DisableChat")->save(); // 0

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
