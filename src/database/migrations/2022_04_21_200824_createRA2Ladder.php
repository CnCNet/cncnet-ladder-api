<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRA2Ladder extends Migration {

	/**
	 * Create RA2 ladder, ra2 sides, ra2 spawn options, ra2 ladder rules.
	 *
	 * @return void
	 */
	public function up()
	{
		#create ra2 ladder
		$ra2Ladder = new \App\Ladder;
		$ra2Ladder->name = 'Red Alert 2';
		$ra2Ladder->abbreviation = 'ra2';
		$ra2Ladder->game = 'yr'; #use yr game
		$ra2Ladder->clans_allowed = 0;
		$ra2Ladder->game_object_schema_id = 1;
		$ra2Ladder->private = 0;
		$ra2Ladder->save();

		$ra2Ladder = \App\Ladder::where('abbreviation', 'ra2')->first();

		$lc = new \App\Http\Controllers\LadderController;
		$lc->addLadder($ra2Ladder->id); #create ladder histories

		#add ra2 sides
		$ra2_sides = ["America", "Korea", "France", "Germany", "Great Britain", "Libya", "Iraq", "Cuba", "Russia"];
		for ($i = 0; $i < count($ra2_sides); ++$i)
		{
			$side = new \App\Side();
			$side->ladder_id = $ra2Ladder->id;
			$side->local_id = $i;
			$side->name = $ra2_sides[$i];
			$side->save();
		}

		#create ra2 ladder rules			
		$ra2LadderRules = new \App\QmLadderRules;
		$ra2LadderRules->ladder_id=$ra2Ladder->id;
		$ra2LadderRules->player_count=2;
		$ra2LadderRules->map_vetoes=8;
		$ra2LadderRules->max_difference=400;
		$ra2LadderRules->all_sides='0,1,2,3,4,5,6,7,8';
		$ra2LadderRules->allowed_sides='0,1,2,3,4,5,6,7,8';
		$ra2LadderRules->bail_time=0;
		$ra2LadderRules->bail_fps=0;
		$ra2LadderRules->tier2_rating=0;
		$ra2LadderRules->rating_per_second=0.75;
		$ra2LadderRules->max_points_difference=400;
		$ra2LadderRules->points_per_second=1;
		$ra2LadderRules->use_elo_points=1;
		$ra2LadderRules->wol_k=64;
		$ra2LadderRules->show_map_preview=1;
		$ra2LadderRules->reduce_map_repeats=5;
		$ra2LadderRules->save();

		#Copy over the YR spawn options to RA2 ladder
		$options = \App\SpawnOptionValue::where('ladder_id', 1)->get();

		foreach ($options as $option) {
			$o = new \App\SpawnOptionValue;
			$o->ladder_id=$ra2Ladder->id;
			$o->spawn_option_id=$option->spawn_option_id;
			$o->value_id=$option->value_id;
			$o->save();
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
