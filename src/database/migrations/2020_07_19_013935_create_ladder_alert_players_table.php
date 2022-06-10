<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLadderAlertPlayersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ladder_alert_players', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('player_id');
            $table->integer('ladder_alert_id');
            $table->boolean('show')->default(true);
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
        Schema::drop('ladder_alert_players');
    }
}
