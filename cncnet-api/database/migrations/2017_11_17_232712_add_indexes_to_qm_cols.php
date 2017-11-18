<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToQmCols extends Migration {

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
            $table->index('qm_match_id');
		});
		Schema::table('qm_matches', function(Blueprint $table)
		{
			//
            $table->index('qm_map_id');
		});
		Schema::table('qm_maps', function(Blueprint $table)
		{
			//
            $table->index('map_id');
		});
		Schema::table('player_ratings', function(Blueprint $table)
		{
			//
            $table->index('player_id');
            $table->index('rating');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
	}
}
