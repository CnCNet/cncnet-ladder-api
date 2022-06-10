<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTiersToPlayerRating extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('player_ratings', function(Blueprint $table)
		{
			//
            $table->integer("tier")->default(1);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('player_ratings', function(Blueprint $table)
		{
			//
            $table->dropColumn("tier");
		});
	}

}
