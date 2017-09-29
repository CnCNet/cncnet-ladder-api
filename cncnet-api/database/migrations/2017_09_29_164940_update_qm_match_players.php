<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateQmMatchPlayers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qm_match_players', function(Blueprint $table)
		{
			//
            $table->string("version")->nullable();
            $table->string("platform")->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qm_match_players', function(Blueprint $table)
		{
			//
            $table->dropColumn("version");
            $table->dropColumn("platform");
		});
	}

}
