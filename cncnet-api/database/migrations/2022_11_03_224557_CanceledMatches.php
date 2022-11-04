<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CanceledMatches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qm_canceled_matches', function (Blueprint $table)
		{
			$table->increments('id');

			$table->integer('qm_match_id')->unsigned();
			$table->integer('player_id')->unsigned();
            $table->integer('ladder_id')->unsigned();

            $table->foreign('qm_match_id')->references('id')->on('qm_matches')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('ladder_id')->references('id')->on('ladders')->onDelete('cascade');

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
        Schema::drop('qm_canceled_matches');
    }
}
