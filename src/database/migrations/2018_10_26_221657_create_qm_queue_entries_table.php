<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQmQueueEntriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qm_queue_entries', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer("qm_match_player_id")->unsigned();
            $table->integer("ladder_history_id")->unsigned();
            $table->integer("rating");
            $table->integer("points");
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
		Schema::drop('qm_queue_entries');
	}

}
