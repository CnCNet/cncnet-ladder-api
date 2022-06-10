<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEloKControl extends Migration {

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
                $table->integer('wol_k')->default(64);
	    });
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
                $table->dropColumn('wol_k');
	    });
    }

}
