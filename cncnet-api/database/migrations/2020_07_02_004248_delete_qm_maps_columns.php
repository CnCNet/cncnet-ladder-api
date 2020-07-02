<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteQmMapsColumns extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('CREATE TABLE qm_maps_backup LIKE qm_maps;');
        DB::statement('INSERT qm_maps_backup SELECT * FROM qm_maps;');

        Schema::table('qm_maps', function(Blueprint $table)
        {
            //
            $table->dropColumn("speed");
            $table->dropColumn("credits");
            $table->dropColumn("bases");
            $table->dropColumn("units");
            $table->dropColumn("tech");
            $table->dropColumn("short_game");
            $table->dropColumn("fog");
            $table->dropColumn("redeploy");
            $table->dropColumn("crates");
            $table->dropColumn("multi_eng");
            $table->dropColumn("allies");
            $table->dropColumn("dog_kill");
            $table->dropColumn("bridges");
            $table->dropColumn("supers");
            $table->dropColumn("build_ally");
            $table->dropColumn("spawn_preview");
            $table->dropColumn("game_mode");
            $table->dropColumn("multi_factory");
            $table->dropColumn("firestorm");
            $table->dropColumn("ra2_mode");
            $table->dropColumn("harv_truce");
            $table->dropColumn("aimable_sams");
            $table->dropColumn("attack_neutral");
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
            $table->dropColumn("aftermath");
            $table->dropColumn("ore_regenerates");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP TABLE qm_maps;");
        DB::statement('CREATE TABLE qm_maps LIKE qm_maps_backup;');
        DB::statement('INSERT qm_maps SELECT * FROM qm_maps_backup;');
        DB::statement('DROP TABLE qm_maps_backup;');
    }
}
