<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOldPtsToGameReport extends Migration {

	/**
	 * Run the migrations.
	 * If a player gets laundered and their points set to 0,
	 * Store the backupPts values in case the launder needs to be undone.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('player_game_reports', function (Blueprint $table) {
			$table->integer('backupPts')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('player_game_reports', function (Blueprint $table) {
			$table->dropColumn('backupPts');
		});
	}

}
