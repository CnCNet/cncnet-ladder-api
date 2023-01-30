<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRandomSpawnColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qm_maps', function (Blueprint $table)
        {
            $table->boolean('random_spawns')->default(false);
        });

        Schema::table('maps', function (Blueprint $table)
        {
            $table->integer('spawn_count')->default(2); //this value is relevant when random_spawns are enabled
        });
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
