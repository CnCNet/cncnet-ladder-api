<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPingsToGameReports extends Migration {

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
            $table->integer("pings_sent")->default(0);
            $table->integer("pings_received")->default(0);
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
            $table->dropColumn("pings_sent");
            $table->dropColumn("pings_received");
		});
	}

}
