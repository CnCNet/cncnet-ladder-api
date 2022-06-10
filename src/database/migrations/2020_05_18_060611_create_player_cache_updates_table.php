<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerCacheUpdatesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	Schema::create('player_cache_updates', function(Blueprint $table)
	    {
                $table->increments('id');
		$table->integer('player_cache_id');
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
	Schema::drop('player_cache_updates');
    }

}
