<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTier2QmLadder extends Migration {

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
            $table->integer("tier2_rating")->default(0);
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
            $table->dropColumn("tier2_rating");
		});
	}

}
