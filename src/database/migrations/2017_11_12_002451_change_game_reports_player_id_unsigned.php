<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeGameReportsPlayerIdUnsigned extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('game_reports', function(Blueprint $table)
		{
			//
            $table->integer("player_id")->unsigned()->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('game_reports', function(Blueprint $table)
		{
			//
		});
	}

}
