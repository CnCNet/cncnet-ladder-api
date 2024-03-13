<?php

use App\Models\SpawnOption;
use App\Models\SpawnOptionType;
use Illuminate\Database\Migrations\Migration;

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
