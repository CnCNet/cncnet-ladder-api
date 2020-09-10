<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClansAllowedToLadder extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ladders', function(Blueprint $table)
        {
            //
            $table->boolean('clans_allowed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ladders', function(Blueprint $table)
        {
            //
            $table->dropColumn('clans_allowed');
        });
    }
}
