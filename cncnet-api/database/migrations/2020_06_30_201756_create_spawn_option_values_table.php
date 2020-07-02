<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpawnOptionValuesTable extends Migration {

    /**
     * Run the migrations.
     *
	* @return void
	*/
    public function up()
    {
        Schema::create('spawn_option_values', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('qm_ladder_rules_id')->nullable();
            $table->integer('qm_map_id')->nullable();
            $table->integer('spawn_option_id');
            $table->integer('value_id');
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
        Schema::drop('spawn_option_values');
    }
}
