<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use \App\SpawnOption;
use \App\SpawnOptionType;

class SeedSpawnOptionDisableSwVsYuri extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Disable SW vs Yuri", "Settings", "DisableSWvsYuri")->save();
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
