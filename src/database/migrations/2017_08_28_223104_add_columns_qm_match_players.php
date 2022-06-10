<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsQmMatchPlayers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qm_match_players', function(Blueprint $table)
		{
			//
            $table->dropColumn('tunnel_ip');
            $table->dropColumn('tunnel_port');

            $table->string('ip_address')->nullable()->change();
            $table->integer('port')->nullable()->change();


            $table->string('ipv6_address')->nullable();
            $table->integer('ipv6_port')->nullable();
            $table->string('lan_ip')->nullable();
            $table->integer('lan_port')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qm_match_players', function(Blueprint $table)
		{
			//
            $table->dropColumn('lan_ip');
            $table->dropColumn('lan_port');
            $table->dropColumn('ipv6_address');
            $table->dropColumn('ipv6_port');

            $table->string('tunnel_ip');
            $table->integer('tunnel_port');
            $table->string('ip_address')->change();
            $table->integer('port')->change();
		});
	}

}
