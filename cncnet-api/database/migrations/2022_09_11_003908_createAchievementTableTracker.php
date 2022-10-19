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
		Schema::drop('achievements_progress');

		Schema::create('achievements_progress', function (Blueprint $table)
		{
			$table->increments('id');
			$table->integer('achievement_id')->unsigned();
			$table->foreign('achievement_id')->references('id')->on('achievements')->onDelete('cascade');
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->timestamp('achievement_unlocked_date')->nullable(true); //the timestamp when the user unlocked the achivement
			$table->integer('count')->default(0);
			$table->timestamps();
		});

		$achievements = \App\Achievement::all();

		$numUsers = \App\User::count();
		$partition = $numUsers / 10;
		$percent = 0;

		$count = 0;
		//initialize Achievement Progress object for every user
		\App\User::chunk(1000, function ($users) use (&$achievements, &$count, &$partition, &$percent)
		{

			foreach ($users as $user)
			{
				$count++;
				if ($count % $partition == 0) {
					$percent += 10;
				
					echo $percent."% completed creating achievements_progress objects for users.";
				}

				foreach ($achievements as $achievement)
				{
					$achievementProgress = new \App\AchievementProgress();
					$achievementProgress->achievement_id = $achievement->id;
					$achievementProgress->user_id = $user->id;
					$achievementProgress->save();
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
		Schema::drop('achievements_progress');
	}

}
