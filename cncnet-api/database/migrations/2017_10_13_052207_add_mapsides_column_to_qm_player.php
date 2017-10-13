<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMapsidesColumnToQmPlayer extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qm_match_players', function(Blueprint $table)
        {
            //
            $table->string("map_sides")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qm_match_players', function(Blueprint $table)
        {
            //
            $table->dropColumn('map_sides');
        });
    }
}
