<?php

use App\Models\Ladder;
use App\Models\QmLadderRules;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateQmLadderRulesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qm_ladder_rules', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('ladder_id');
            $table->integer('player_count');
            $table->integer('map_vetoes');
            $table->integer('max_difference');
            $table->string('all_sides');    // eg "0,1,2,3,4,5,6,7,8,9"
            $table->string('allowed_sides'); // minus france/yuri "0,1,3,4,5,6,7,8"
			$table->timestamps();
		});
        $yr_entry = new QmLadderRules();
        $yr_entry->ladder_id = Ladder::where('abbreviation', 'yr')->first()->id;
        $yr_entry->player_count = 2;
        $yr_entry->all_sides = "0,1,2,3,4,5,6,7,8,9";
        $yr_entry->allowed_sides = "0,1,3,4,5,6,7,8";
        $yr_entry->map_vetoes = 3;
        $yr_entry->max_difference = 100;
        $yr_entry->save();

        $ts_entry = new QmLadderRules();
        $ts_entry->ladder_id = Ladder::where('abbreviation', 'ts')->first()->id;
        $ts_entry->player_count = 2;
        $ts_entry->all_sides = "0,1";
        $ts_entry->allowed_sides = "0,1";
        $ts_entry->map_vetoes = 3;
        $ts_entry->max_difference = 100;
        $ts_entry->save();

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('qm_ladder_rules');
	}

}
