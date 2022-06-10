<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFiltersToQmRules extends Migration {

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
            $table->double('rating_per_second');
            $table->integer('max_points_difference');
            $table->double('points_per_second');

		});
        $qmLadders = \App\QmLadderRules::all();
        foreach ($qmLadders as $qmRules)
        {
            $qmRules->rating_per_second = 0.25;
            $qmRules->max_points_difference = 400;
            $qmRules->points_per_second = 0.25;
            $qmRules->save();
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
            $table->dropColumn('rating_per_second');
            $table->dropColumn('points_per_second');
            $table->dropColumn('max_points_difference');
		});
	}

}
