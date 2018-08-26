<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQmMatchStatesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('state_types', function(Blueprint $table)
		{
			$table->increments('id');
            $table->string('name');
		});

		Schema::create('qm_match_states', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('player_id')->unsigned();
            $table->integer('qm_match_id')->unsigned();
            $table->integer('state_type_id')->unsigned();
			$table->timestamps();
		});

        Schema::table('qm_matches', function(Blueprint $table)
        {
            $table->dropColumn('status');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('state_types');
		Schema::drop('qm_match_states');
	}

}
