<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditPlayerPointsTable extends Migration 
{
	public function up()
	{
        Schema::table('player_points', function(Blueprint $table)
		{
            $table->integer('ladder_history_id')->unsigned();
		});	
    }

	public function down()
	{
        Schema::table('player_points', function(Blueprint $table)
		{
            $table->dropColumn(['ladder_history_id']);
		});	
	}
}
