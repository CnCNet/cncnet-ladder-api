<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditLadderGamesTable extends Migration 
{
	public function up()
	{
        Schema::table('ladder_games', function(Blueprint $table)
		{
            $table->renameColumn('ladder_id', 'ladder_history_id');
		});
	}

	public function down()
	{
        Schema::table('ladder_games', function(Blueprint $table)
		{
            $table->renameColumn('ladder_history_id', 'ladder_id');
		});
	}
}
