<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFrameSendRate extends Migration {

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
                    $table->integer('frame_send_rate')->default(-1);
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
                    $table->dropColumn('frame_send_rate');
		});
	}

}
