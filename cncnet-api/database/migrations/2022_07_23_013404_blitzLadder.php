<?php

use Illuminate\Database\Migrations\Migration;

class BlitzLadder extends Migration {

	/* Create a Blitz Ladder.
	*
	* @return void
	*/
   public function up()
   {
	   $yrLadder = \App\Models\Ladder::where('abbreviation', 'yr')->first();

	   #create blitz ladder copying from the yr ladder
	   $blitzLadder = $yrLadder->replicate()->fill([
		   'name' => 'Blitz',
		   'abbreviation' => 'blitz',
	   ]);
	   $blitzLadder->save();

	   $lc = new \App\Http\Controllers\LadderController;
	   $lc->addLadder($blitzLadder->id); #create ladder histories

	   #add sides
	   $sides = \App\Models\Side::where('ladder_id', $yrLadder->id)->get();
	   for ($i = 0; $i < count($sides); ++$i)
	   {
		   $side = new \App\Models\Side();
		   $side->ladder_id = $blitzLadder->id;
		   $side->local_id = $i;
		   $side->name = $sides[$i]->name;
		   $side->save();
	   }

	   #create ladder rules			
	   $yrLadderRules = \App\Models\QmLadderRules::where('ladder_id', $yrLadder->id)->first();
	   $newLadderRules = $yrLadderRules->replicate()->fill([
		   'ladder_id' => $blitzLadder->id
	   ]);
	   $newLadderRules->save();

	   #Copy over the YR spawn options
	   $options = \App\Models\SpawnOptionValue::where('ladder_id', $yrLadder->id)->get();

	   foreach ($options as $option)
	   {
		   $o = new \App\Models\SpawnOptionValue;
		   $o->ladder_id = $blitzLadder->id;
		   $o->spawn_option_id = $option->spawn_option_id;
		   $o->value_id = $option->value_id;
		   $o->save();
	   }
   }

   /**
	* Reverse the migrations, delete Blitz ladder data.
	*
	* @return void
	*/
   public function down()
   {
	   $blitzLadder = \App\Models\Ladder::where('name', 'blitz')->first();
	   \App\Models\SpawnOptionValue::where('ladder_id', $blitzLadder->id)->delete();
	   \App\Models\QmLadderRules::where('ladder_id', $blitzLadder->id)->delete();
	   \App\Models\Side::where('ladder_id', $blitzLadder->id)->delete();

	   $blitzLadder->delete();
   }


}
