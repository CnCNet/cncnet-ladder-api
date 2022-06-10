<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGameReportsTable extends Migration {

    /**
	 * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_reports', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('game_id')->unsigned();
            $table->boolean('player_id');     // The reporter
            $table->boolean('best_report');   // Is this currently the best report for this game?
            $table->boolean('manual_report'); // Was this report created by the API or by an admin
            $table->integer('duration');      // duration, fps and stats are disputable so they go here rather than games table
            $table->boolean('valid');         // Did this game meet the requirements to count for points? (dura+fps)
            $table->boolean('finished');
            $table->integer('fps');
            $table->boolean('oos');
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
        Schema::drop('game_reports');
    }
}
