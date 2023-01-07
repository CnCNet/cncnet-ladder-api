<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeaguePlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('league_players', function (Blueprint $table)
        {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('ladder_id');

            $table->boolean('can_play_both_tiers')->default(true);

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('ladder_id')->references('id')->on('ladders');
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
        //
    }
}
