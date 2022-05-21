<?php

use App\Models\GameObjectSchema;
use App\Models\ObjectSchemaManager;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

class AddObjectManager extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$game_objects = GameObjectSchema::all();

		#add Alex P as object game schema manager
		foreach ($game_objects as $game_object)
		{
			$osm = new ObjectSchemaManager;
			$osm->game_object_schema_id = $game_object->id;
			$osm->user_id = User::where('email', 'amp1993@gmail.com')->first()->id;
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
