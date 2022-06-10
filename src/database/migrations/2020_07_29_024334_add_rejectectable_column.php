<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRejectectableColumn extends Migration {

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
            $table->boolean('rejectable')->default(true);
            $table->boolean('default_reject')->default(false);
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
            $table->dropColumn('rejectable');
            $table->dropColumn('default_reject');
        });
    }
}
