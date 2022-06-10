<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerCachesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('player_caches', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer("ladder_history_id")->unsigned();
            $table->integer("player_id")->unsigned();
            $table->string("player_name");
            $table->integer("card")->nullable();
            $table->integer("points");
            $table->integer("wins");
            $table->integer("games");
            $table->integer("percentile");
            $table->integer("side")->nullable();
            $table->integer("fps");

            $table->index(["ladder_history_id", "player_id"]);
            $table->index(["ladder_history_id", "points"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('player_caches');
	}

}
