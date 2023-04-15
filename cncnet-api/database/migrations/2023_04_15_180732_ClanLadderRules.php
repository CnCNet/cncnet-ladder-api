<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ClanLadderRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clan_ladder_rules', function(Blueprint $table)
		{
			$table->increments('id')->unsigned()->nullable(false);
            $table->integer('ladder_id')->unsigned()->nullable(false);
            $table->integer('max_clans_allowed');

            $table->foreign('ladder_id')->references('id')->on('ladders')->onDelete('cascade');
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
