<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBailTimeToQmRules extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qm_ladder_rules', function(Blueprint $table)
		{
			//
            $table->integer("bail_time")->default(60);
            $table->integer("bail_fps")->default(30);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qm_ladder_rules', function(Blueprint $table)
		{
			//
            $table->dropColumn("bail_time");
            $table->dropColumn("bail_fps");
		});
	}

}
