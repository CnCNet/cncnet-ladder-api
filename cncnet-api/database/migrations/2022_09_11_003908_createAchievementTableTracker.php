<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementTableTracker extends Migration {

	/**
	 * A table to track the player's locked/unlocked achievements as well as track progress towards unlocking an achievement.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('achievements_tracker', function (Blueprint $table)
		{
			$table->increments('id');
			$table->integer('achievement_id')->nullable(false);
			$table->foreign('achievement_id')->references('id')->on('achievements')->onDelete('cascade');
			$table->integer('user_id')->nullable(false);
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->timestamp('achievement_unlocked_date')->nullable(true); //the timestamp when the user unlocked the achivement
			$table->integer('count')->default(0);
		});

		$achievements = \App\Achievement::all();

		//initialize Achievement Tracker object for every user
		\App\User::chunk(1000, function ($users) use (&$achievements)
		{
			foreach ($users as $user)
			{
				foreach ($achievements as $achievement)
				{
					$achievementTracker = new \App\AchievementTracker();
					$achievementTracker->achievement_id = $achievement->id;
					$achievementTracker->user_id = $user->id;
					$achievementTracker->save();
				}
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('achievements_tracker');
	}

}
