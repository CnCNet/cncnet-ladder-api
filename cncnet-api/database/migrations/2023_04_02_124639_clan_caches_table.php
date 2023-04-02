<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ClanCachesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clan_caches', function (Blueprint $table)
        {
            $table->increments('id');
            $table->unsignedInteger("ladder_history_id");
            $table->unsignedInteger("clan_id");
            $table->string("clan_name");
            $table->integer("points");
            $table->integer("wins");
            $table->integer("games");
            $table->integer("side")->nullable();
            $table->integer("fps");

            $table->foreign('ladder_history_id')->references('id')->on('ladder_history');
            $table->foreign('clan_id')->references('id')->on('clans');
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
