<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_ratings', function (Blueprint $table)
        {
            $table->increments("id");
            $table->unsignedInteger("user_id");
            $table->integer("rating");
            $table->integer("peak_rating");
            $table->integer("rated_games");
            $table->timestamps();
        });

        Schema::table('user_ratings', function (Blueprint $table)
        {
            $table->index('user_id');
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
    }
}
