<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQmColumns extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qm_maps', function(Blueprint $table)
        {
            //
            $table->string('game_mode')->nullable();
            $table->boolean('multi_factory')->nullable();
            $table->boolean('firestorm')->nullable();
            $table->boolean('ra2_mode')->nullable();
            $table->boolean('harv_truce')->nullable();
            $table->boolean('aimable_sams')->nullable();
            $table->boolean('attack_neutral')->nullable();
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
            $table->dropColumn('game_mode');
            $table->dropColumn('multi_factory');
            $table->dropColumn('firestorm');
            $table->dropColumn('ra2_mode');
            $table->dropColumn('harv_truce');
            $table->dropColumn('aimable_sams')->nullable();
            $table->dropColumn('attack_neutral')->nullable();
        });
    }
}
