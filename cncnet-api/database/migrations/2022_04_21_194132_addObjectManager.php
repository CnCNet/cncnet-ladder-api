<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddObjectManager extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$game_objects = \App\GameObjectSchema::all();

		#add Alex P as object game schema manager
		foreach ($game_objects as $game_object) {
			$osm = new \App\ObjectSchemaManager;
			$osm->game_object_schema_id=$game_object->id;
			$osm->user_id=\App\User::where('email', 'amp1993@gmail.com')->first()->id;
			$osm->save();
		}
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
