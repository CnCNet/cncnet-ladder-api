<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQmConnectionStatsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qm_connection_stats', function(Blueprint $table)
	    {
            $table->increments('id');
            $table->integer('qm_match_id');
            $table->integer('player_id');
            $table->integer('peer_id');
            $table->integer('ip_address_id');
            $table->integer('port');
            $table->integer('rtt');
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
        Schema::drop('qm_connection_stats');
    }
}
