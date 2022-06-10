<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGamesRawTable extends Migration 
{
	public function up()
	{
        Schema::create('games_raw', function (Blueprint $table) 
        {
            $table->increments('id');
            $table->string('hash');
            $table->string('packet');
            $table->integer('game_id')->unsigned();
            $table->integer('ladder_id')->unsigned();
        });
	}
}
