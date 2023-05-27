<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClanRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clan_ratings', function (Blueprint $table)
        {
            $table->increments("id");
            $table->unsignedInteger("clan_id");
            $table->integer("rating");
            $table->integer("peak_rating");
            $table->integer("rated_games");
            $table->timestamps();

            $table->foreign('clan_id')->references('id')->on('clans');
        });

        Schema::table('clan_ratings', function (Blueprint $table)
        {
            $table->index('clan_id');
            $table->index('rating');
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
