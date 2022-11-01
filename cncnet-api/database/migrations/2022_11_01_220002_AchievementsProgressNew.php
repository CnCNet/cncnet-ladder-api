<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AchievementsProgressNew extends Migration
{
    /**
     * Create Achievements Progress table
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('achievements_progress');

        Schema::create('achievements_progress', function (Blueprint $table)
		{
			$table->increments('id');
			$table->integer('achievement_id')->unsigned();
			$table->foreign('achievement_id')->references('id')->on('achievements')->onDelete('cascade');
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->timestamp('achievement_unlocked_date')->nullable(true); //the timestamp when the user unlocked the achievement
			$table->integer('count')->default(0);
			$table->timestamps();
		});
    }

    /**
     * Remove table
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('achievements_progress');
    }
}
