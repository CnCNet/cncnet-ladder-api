<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateQmMapsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qm_maps', function(Blueprint $table)
		{
            // metadata
			$table->increments('id');
            $table->integer('ladder_id');
            $table->integer('map_id');

            // A name to give the map since the same map could appear multiple times per game
            // with different settings e.g. "Heck Freezes Over Bottom Left vs Bottom Right"
            $table->string('description');

            // QM will have a max of 31 maps. Set this to 0-30 can overlap with other games(TS/YR/RA)
            $table->integer('bit_idx');

            // set valid = false to delete a map
            $table->boolean('valid');

            // Order of spawn locations to use. e.g. "3,1,4,2"
            // this only is a factor when the map has more spawn locations than players
            $table->string('spawn_order');


            // spawn options
            $table->integer('speed');
            $table->integer('credits');
            $table->boolean('bases');
            $table->integer('units');
            $table->string('game_mode')->nullable();
            $table->integer('tech')->nullable();
            $table->boolean('short_game')->nullable();
            $table->boolean('fog')->nullable();
            $table->boolean('redeploy')->nullable();
            $table->boolean('crates')->nullable();
            $table->boolean('multi_eng')->nullable();
            $table->boolean('allies')->nullable();
            $table->boolean('dog_kill')->nullable();
            $table->boolean('bridge_destroy')->nullable();
            $table->boolean('supers')->nullable();
            $table->boolean('build_ally')->nullable();
            $table->boolean('spawn_preview')->nullable();
            $table->boolean('firestorm')->nullable();
            $table->boolean('harvester_truce')->nullable();
            $table->boolean('multi_factory')->nullable();
            $table->boolean('aimable_sams')->nullable();
            $table->boolean('attack_neutral')->nullable();
            $table->string('team1')->nullable();
            $table->string('team2')->nullable();
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
		Schema::drop('qm_maps');
	}

}
