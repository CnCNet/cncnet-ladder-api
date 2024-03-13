<?php

use App\Models\SpawnOptionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSpawnOptionTypesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spawn_option_types', function(Blueprint $table)
        {
            $table->increments('id');
            $table->text('name');
            $table->timestamps();
        });

        (new SpawnOptionType("SPAWN.INI Option"))->save();
        (new SpawnOptionType("SPAWNMAP.INI Option"))->save();
        (new SpawnOptionType("Copy File"))->save();
        (new SpawnOptionType("Append File to File"))->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('spawn_option_types');
    }
}
