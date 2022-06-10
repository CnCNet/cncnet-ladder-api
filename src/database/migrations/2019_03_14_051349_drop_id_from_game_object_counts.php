<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropIdFromGameObjectCounts extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('game_object_counts', function(Blueprint $table)
		{
			//
            $table->dropColumn('id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('game_object_counts', function(Blueprint $table)
		{
			//can't roll back
		});
	}

}
