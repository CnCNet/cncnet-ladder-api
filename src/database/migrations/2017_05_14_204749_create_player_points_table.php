<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerPointsTable extends Migration 
{
	public function up()
	{
        Schema::create('player_points', function (Blueprint $table) 
        {
            $table->increments('id');
            $table->integer('points_awarded');
            $table->boolean('game_won');
            $table->integer('game_id')->unsigned();
            $table->integer('player_id')->unsigned();
        });
	}
}
