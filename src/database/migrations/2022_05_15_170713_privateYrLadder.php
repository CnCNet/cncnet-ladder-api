<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PrivateYrLadder extends Migration
{

	/**
	 * Create a yuri's revenge test ladder.
	 *
	 * @return void
	 */
	public function up()
	{
		$yrLadder = \App\Ladder::where('abbreviation', 'yr')->first();

		#create test ladder
		$testLadder = $yrLadder->replicate()->fill([
			'name' => 'Yuri\'s Revenge Test',
			'abbreviation' => 'yr-test',
			'private' => 1
		]);
		$testLadder->save();

		$testLadder = \App\Ladder::where('abbreviation', 'yr-test')->first();

		$lc = new \App\Http\Controllers\LadderController;
		$lc->addLadder($testLadder->id); #create ladder histories

		#add sides
		$sides = \App\Models\Side::where('ladder_id', $yrLadder->id)->get();
		for ($i = 0; $i < count($sides); ++$i)
		{
			$side = new \App\Models\Side();
			$side->ladder_id = $testLadder->id;
			$side->local_id = $i;
			$side->name = $sides[$i]->name;
			$side->save();
		}

		#create ladder rules			
		$yrLadderRules = \App\QmLadderRules::where('ladder_id', $yrLadder->id)->first();
		$newLadderRules = $yrLadderRules->replicate()->fill([
			'ladder_id' => $testLadder->id
		]);
		$newLadderRules->save();

		#Copy over the YR spawn options
		$options = \App\SpawnOptionValue::where('ladder_id', $yrLadder->id)->get();

		foreach ($options as $option)
		{
			$o = new \App\SpawnOptionValue;
			$o->ladder_id = $testLadder->id;
			$o->spawn_option_id = $option->spawn_option_id;
			$o->value_id = $option->value_id;
			$o->save();
		}

		#Copy over existing YR ladder Map Pool
		$newPool = new \App\Models\MapPool;
		$newPool->name = 'Test Map Pool';
		$newPool->ladder_id = $testLadder->id;
		$newPool->save();

		#copy over maps
		$yrMaps = \App\Models\Map::where('ladder_id', $yrLadder->id)->get();
		foreach ($yrMaps as $yrMap)
		{
			$newMap = $yrMap->replicate()->fill([
				'ladder_id' => $testLadder->id,
			]);
			$newMap->save();
		}

		$yrQmMaps = \App\QmMap::where('map_pool_id', $yrLadder->map_pool_id)->get();
		#copy yr qm maps
		foreach ($yrQmMaps as $yrQmMap)
		{
			$map_id = \App\Models\Map::where('ladder_id', $yrLadder->id)
				->where('hash', $yrQmMap->map->hash)
				->first()->id;

			$newQmMap = $yrQmMap->replicate()->fill([
				'ladder_id' => $testLadder->id,
				'map_pool_id' => $newPool->id,
				'map_id' => $map_id
			]);
			$newQmMap->valid = 1;
			$newQmMap->save();
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		$testLadder = \App\Ladder::where('abbreviation', 'yr-test')->first();
		\App\QmMap::where('map_pool_id', $testLadder->map_pool_id)->delete();
		\App\Models\Map::where('ladder_id', $testLadder->id)->delete();
		\App\SpawnOptionValue::where('ladder_id', $testLadder->id)->delete();
		\App\QmLadderRules::where('ladder_id', $testLadder->id)->delete();
		\App\Models\Side::where('ladder_id', $testLadder->id)->delete();

		$testLadder->delete();
	}
}
