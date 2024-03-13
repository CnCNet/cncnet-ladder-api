<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddColumnsForRa extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        $ladder = \App\Models\Ladder::where("abbreviation", "ra")->first();

        if ($ladder !== null)
        {
            $side = new \App\Models\Side();
            $side->ladder_id = $ladder->id;
            $side->local_id = -1;
            $side->name = "Random";
            $side->save();

            $side = new \App\Models\Side();
            $side->ladder_id = $ladder->id;
            $side->local_id = 0;
            $side->name = "Spain";
            $side->save();

            $side = new \App\Models\Side();
            $side->ladder_id = $ladder->id;
            $side->local_id = 1;
            $side->name = "Greece";
            $side->save();

            $side = new \App\Models\Side();
            $side->ladder_id = $ladder->id;
            $side->local_id = 2;
            $side->name = "Russia";
            $side->save();

            $side = new \App\Models\Side();
            $side->ladder_id = $ladder->id;
            $side->local_id = 3;
            $side->name = "England";
            $side->save();

            $side = new \App\Models\Side();
            $side->ladder_id = $ladder->id;
            $side->local_id = 4;
            $side->name = "Ukraine";
            $side->save();

            $side = new \App\Models\Side();
            $side->ladder_id = $ladder->id;
            $side->local_id = 5;
            $side->name = "Germany";
            $side->save();

            $side = new \App\Models\Side();
            $side->ladder_id = $ladder->id;
            $side->local_id = 6;
            $side->name = "France";
            $side->save();

            $side = new \App\Models\Side();
            $side->ladder_id = $ladder->id;
            $side->local_id = 7;
            $side->name = "Turkey";
            $side->save();
        }

		Schema::table('qm_maps', function(Blueprint $table)
		{
			//
            $table->boolean("fix_ai_ally")->nullable();
            $table->boolean("ally_reveal")->nullable();
            $table->boolean("am_fast_build")->nullable();
            $table->boolean("parabombs")->nullable();
            $table->boolean("fix_formation_speed")->nullable();
            $table->boolean("fix_magic_build")->nullable();
            $table->boolean("fix_range_exploit")->nullable();
            $table->boolean("super_tesla_fix")->nullable();
            $table->boolean("forced_alliances")->nullable();
            $table->boolean("tech_center_fix")->nullable();
            $table->boolean("no_screen_shake")->nullable();
            $table->boolean("no_tesla_delay")->nullable();
            $table->boolean("dead_player_radar")->nullable();
            $table->boolean("capture_flag")->nullable();
            $table->boolean("slow_unit_build")->nullable();
            $table->boolean("shroud_regrows")->nullable();

            $table->boolean("ai_player_count")->default(0);
            $table->string("team1_spawn_order");
            $table->string("team2_spawn_order");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qm_maps', function(Blueprint $table)
		{
			//
            $table->dropColumn("fix_ai_ally");
            $table->dropColumn("ally_reveal");
            $table->dropColumn("am_fast_build");
            $table->dropColumn("parabombs");
            $table->dropColumn("fix_formation_speed");
            $table->dropColumn("fix_magic_build");
            $table->dropColumn("fix_range_exploit");
            $table->dropColumn("super_tesla_fix");
            $table->dropColumn("forced_alliances");
            $table->dropColumn("tech_center_fix");
            $table->dropColumn("no_screen_shake");
            $table->dropColumn("no_tesla_delay");
            $table->dropColumn("dead_player_radar");
            $table->dropColumn("capture_flag");
            $table->dropColumn("slow_unit_build");
            $table->dropColumn("shroud_regrows");

            $table->dropColumn("ai_player_count");
            $table->dropColumn("team1_spawn_order");
            $table->dropColumn("team2_spawn_order");
		});
	}

}
