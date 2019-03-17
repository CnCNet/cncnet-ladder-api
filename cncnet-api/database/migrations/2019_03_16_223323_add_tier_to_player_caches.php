<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTierToPlayerCaches extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('player_caches', function(Blueprint $table)
		{
			//
            $table->integer('tier')->default(1);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('player_caches', function(Blueprint $table)
		{
			//
            $table->dropColumn('tier');
		});
	}

}
