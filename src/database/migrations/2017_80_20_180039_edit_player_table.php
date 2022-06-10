<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditPlayerTable extends Migration 
{
	public function up()
	{
		Schema::table('players', function(Blueprint $table)
		{
            $table->integer('card_id')->unsigned();
		});
	}

    public function down()
    {

    }
}
