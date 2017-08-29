<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveColumnsQmMatches extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qm_matches', function(Blueprint $table)
		{
			//
            $table->dropColumn('tunnel_ip');
            $table->dropColumn('tunnel_port');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qm_matches', function(Blueprint $table)
		{
			//
            $table->string('tunnel_ip');
            $table->integer('tunnel_port');
		});
	}

}
