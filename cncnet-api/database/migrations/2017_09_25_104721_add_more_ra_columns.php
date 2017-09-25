<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreRaColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qm_maps', function(Blueprint $table)
		{
			//
            $table->boolean('aftermath')->nullable();
            $table->boolean('ore_regenerates')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qm_maps', function(Blueprint $table)
		{
			//
            $table->dropColumn('aftermath');
            $table->dropColumn('ore_regenerates');
		});
	}

}
