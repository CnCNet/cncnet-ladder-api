<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFreeVetoColumn extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qm_maps', function (Blueprint $table)
		{
			$table->integer("free_veto")
				->nullable()
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
		Schema::table('qm_maps', function (Blueprint $table)
		{
			$table->dropColumn("free_veto");
		});
	}
}
