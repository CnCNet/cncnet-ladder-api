<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIpColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('bans', function(Blueprint $table)
		{
			//
            $table->integer("ip_address_id")->nullable();
		});
		Schema::table('users', function(Blueprint $table)
		{
			//
            $table->integer("ip_address_id")->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('bans', function(Blueprint $table)
		{
			//
            $table->dropColumn("ip_address_id");
		});
		Schema::table('users', function(Blueprint $table)
		{
			//
            $table->dropColumn("ip_address_id");
		});
	}

}
