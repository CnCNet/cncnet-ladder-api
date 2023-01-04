<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class QmMatchTier extends Migration
{
    /**
     * Run the migrations. Create tier column.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qm_match_players', function(Blueprint $table)
        {
            $table->integer("tier")->nullable(false);
        });

        Schema::table('qm_matches', function(Blueprint $table)
        {
            $table->integer("tier")->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
