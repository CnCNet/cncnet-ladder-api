<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use \App\SpawnOptionString;

class CreateSpawnOptionStringsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spawn_option_strings', function(Blueprint $table)
        {
            $table->increments('id');
            $table->text('string');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('spawn_option_strings');
    }
}
