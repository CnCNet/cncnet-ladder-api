<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGameColsQmGame extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('games', function(Blueprint $table)
		{
			//
            $table->integer('qm_match_id')->unsigned()->nullable();
		});
		Schema::table('qm_matches', function(Blueprint $table)
		{
			//
            $table->integer('game_id')->unsigned()->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('games', function(Blueprint $table)
		{
			//
            $table->dropColumn('qm_match_id');
		});

		Schema::table('qm_matches', function(Blueprint $table)
		{
			//
            $table->dropColumn('game_id');
		});
	}
}
