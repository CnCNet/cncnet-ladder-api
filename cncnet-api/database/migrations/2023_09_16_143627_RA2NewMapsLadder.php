<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RA2NewMapsLadder extends Migration
{
/**
	 * Create a RA2-new-maps Ladder.
	 *
	 * @return void
	 */
	public function up()
	{
		$ra2Ladder = \App\Ladder::where('abbreviation', 'ra2')->first();

		#create test ladder
		$ra2TestLadder = $ra2Ladder->replicate()->fill([
			'name' => 'RA2 Beta',
			'abbreviation' => 'ra2-beta',
            'order' => 10
		]);
		$ra2TestLadder->save();

		$lc = new \App\Http\Controllers\LadderController;
		$lc->addLadder($ra2TestLadder->id); #create ladder histories

		#add sides
		$sides = \App\Side::where('ladder_id', $ra2Ladder->id)->get();
		for ($i = 0; $i < count($sides); ++$i)
		{
			$side = new \App\Side();
			$side->ladder_id = $ra2TestLadder->id;
			$side->local_id = $i;
			$side->name = $sides[$i]->name;
			$side->save();
		}

		#create ladder rules			
		$ra2LadderRules = \App\QmLadderRules::where('ladder_id', $ra2Ladder->id)->first();
		$newLadderRules = $ra2LadderRules->replicate()->fill([
			'ladder_id' => $ra2TestLadder->id
		]);
		$newLadderRules->save();

		#Copy over the RA2 spawn options
		$options = \App\SpawnOptionValue::where('ladder_id', $ra2Ladder->id)->get();

		foreach ($options as $option)
		{
			$o = new \App\SpawnOptionValue;
			$o->ladder_id = $ra2TestLadder->id;
			$o->spawn_option_id = $option->spawn_option_id;
			$o->value_id = $option->value_id;
			$o->save();
		}
	}

	/**
	 * Reverse the migrations, delete ra2-new-maps ladder data.
	 *
	 * @return void
	 */
	public function down()
	{
		$testLadder = \App\Ladder::where('abbreviation', 'ra2-beta')->first();
		\App\SpawnOptionValue::where('ladder_id', $testLadder->id)->delete();
		\App\QmLadderRules::where('ladder_id', $testLadder->id)->delete();
		\App\Side::where('ladder_id', $testLadder->id)->delete();

		$testLadder->delete();
	}

}
