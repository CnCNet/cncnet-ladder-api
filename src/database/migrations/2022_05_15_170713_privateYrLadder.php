<?php

use App\Models\Ladder;
use App\Models\Map;
use App\Models\QmLadderRules;
use App\Models\QmMap;
use App\Models\Side;
use App\Models\SpawnOptionValue;
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
		$yrLadder = Ladder::where('abbreviation', 'yr')->first();

		#create test ladder
		$testLadder = $yrLadder->replicate()->fill([
			'name' => 'Yuri\'s Revenge Test',
			'abbreviation' => 'yr-test',
			'private' => 1
		]);
		$testLadder->save();

		$testLadder = Ladder::where('abbreviation', 'yr-test')->first();

		$lc = new \App\Http\Controllers\LadderController;
		$lc->addLadder($testLadder->id); #create ladder histories

		#add sides
		$sides = Side::where('ladder_id', $yrLadder->id)->get();
		for ($i = 0; $i < count($sides); ++$i)
		{
			$side = new Side();
			$side->ladder_id = $testLadder->id;
			$side->local_id = $i;
			$side->name = $sides[$i]->name;
			$side->save();
		}

		#create ladder rules			
		$yrLadderRules = QmLadderRules::where('ladder_id', $yrLadder->id)->first();
		$newLadderRules = $yrLadderRules->replicate()->fill([
			'ladder_id' => $testLadder->id
		]);
		$newLadderRules->save();

		#Copy over the YR spawn options
		$options = App\Models\SpawnOptionValue::where('ladder_id', $yrLadder->id)->get();

		foreach ($options as $option)
		{
			$o = new App\Models\SpawnOptionValue;
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

		$yrQmMaps = QmMap::where('map_pool_id', $yrLadder->map_pool_id)->get();
		#copy yr qm maps
		foreach ($yrQmMaps as $yrQmMap)
		{
			$map_id = Map::where('ladder_id', $yrLadder->id)
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
		$testLadder = Ladder::where('abbreviation', 'yr-test')->first();
		QmMap::where('map_pool_id', $testLadder->map_pool_id)->delete();
		Map::where('ladder_id', $testLadder->id)->delete();
		SpawnOptionValue::where('ladder_id', $testLadder->id)->delete();
		QmLadderRules::where('ladder_id', $testLadder->id)->delete();
		Side::where('ladder_id', $testLadder->id)->delete();

		$testLadder->delete();
	}
}
