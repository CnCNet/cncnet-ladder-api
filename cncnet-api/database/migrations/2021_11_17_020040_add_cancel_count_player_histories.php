<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCancelCountPlayerHistories extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('player_histories', function (Blueprint $table)
		{
			$table->integer('cancels')
				->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('player_histories', function (Blueprint $table)
		{
			$table->dropColumn('cancels');
		});
	}
}
