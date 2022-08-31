<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserSettingsTable extends Migration {

	/**
	 * Run the migrations.
	 * 
	 * Create a table for QM User settings
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_settings', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')
				  ->references('id')->on('users')
				  ->onDelete('cascade');
			$table->boolean('enableAnonymous')->default(false);	//flag to determine if player is 'anonymous', their player details will be hidden from player profile
			$table->boolean('disabledPointFilter')->default(false); //flag to determine if when entering queue the point filter will be ignored when matching players who also have it disabled
		});

		//initialize user settings for all users
		\App\User::chunk(500, function ($allUsers)  {
			foreach ($allUsers as $user)
			{
				$userSettings = new \App\UserSettings();
				$userSettings->user_id = $user->id;
				$userSettings->save();
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
		Schema::drop('user_settings', function(Blueprint $table) {
			$table->dropForeign('user_settings_user_id_foreign');
			$table->dropColumn('user_id');
		});
	}

}
