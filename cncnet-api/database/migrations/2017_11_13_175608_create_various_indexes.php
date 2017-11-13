<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVariousIndexes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('player_game_reports', function(Blueprint $table)
		{
			//
            $table->index('player_id');
            $table->index('game_report_id');
		});

		Schema::table('game_reports', function(Blueprint $table)
		{
			//
            $table->index('game_id');
		});

		Schema::table('games', function(Blueprint $table)
		{
			//
            $table->index('game_report_id');
		});

        Schema::table('player_histories', function(Blueprint $table)
		{
			//
            $table->index('player_id');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('player_game_reports', function(Blueprint $table)
		{
			//
		});
	}

}
