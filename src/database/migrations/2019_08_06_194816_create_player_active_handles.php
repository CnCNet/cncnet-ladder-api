<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerActiveHandles extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('player_active_handles', function(Blueprint $table)
		{
            $table->increments('id');
            $table->integer('ladder_id');
			$table->integer('player_id');
			$table->integer('user_id');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('player_active_handles', function(Blueprint $table)
		{
            $table->dropColumn('id');
            $table->dropColumn('ladder_id');
            $table->dropColumn('player_id');
            $table->dropColumn('user_id');
		});
	}

}
