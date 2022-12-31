<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class GameAudit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_audit', function (Blueprint $table)
		{
            $table->increments('id');
            $table->integer('game_id')->unsigned()->nullable(false);
            $table->integer('user_id')->unsigned()->nullable(false);
            $table->integer('ladder_history_id')->unsigned()->nullable(false);

            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ladder_history_id')->references('id')->on('ladder_history')->onDelete('cascade');

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
