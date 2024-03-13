<?php

use Illuminate\Database\Migrations\Migration;

class Grandcannonrangenerf extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\SpawnOption::makeOne(\App\Models\SpawnOptionType::SPAWNMAP_INI, "GrandCannonWeapon.Range", "GrandCannonWeapon", "Range")->save();
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
