<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CorrectQmLadderRulesFranceYuri extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qm_ladder_rules', function(Blueprint $table)
		{
			//
		});
        $qmRules = \App\Models\Ladder::where('abbreviation', 'yr')->first()->qmLadderRules()->first();
        if ($qmRules !== null)
        {
            $qmRules->all_sides = "0,1,2,3,4,5,6,7,8,9";
            $qmRules->save();

            $side = new \App\Models\Side;
            $side->local_id = 2;
            $side->name = "France";
            $side->ladder_id = $qmRules->ladder_id;
            $side->save();

            $side = new \App\Models\Side;
            $side->local_id = 9;
            $side->name = "Yuri";
            $side->ladder_id = $qmRules->ladder_id;
            $side->save();
        }

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qm_ladder_rules', function(Blueprint $table)
		{
			//
		});
	}

}
