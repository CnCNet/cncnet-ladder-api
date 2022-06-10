<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateQmMatchPlayersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qm_match_players', function(Blueprint $table)
		{
			$table->bigIncrements('id');
            $table->boolean('waiting');
            $table->integer('player_id');
            $table->integer('ladder_id');
            $table->integer('map_bitfield');
            $table->integer('chosen_side');
            $table->integer('actual_side');
            $table->string('ip_address');
            $table->integer('port');
            $table->string('tunnel_ip');
            $table->integer('tunnel_port');
            $table->integer('color')->nullable();
            $table->integer('location')->nullable();
            $table->bigInteger('qm_match_id')->nullable();
            $table->integer('tunnel_id')->nullable();
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
		Schema::drop('qm_match_players');
	}

}
