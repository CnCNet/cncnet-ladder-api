<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LadderRulesPtFilterThreshold extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qm_ladder_rules', function (Blueprint $table)
        {
            $table->integer('point_filter_rank_threshold')->default(50); //QM player must be at least this rank for 'disable pt filter' to be applied
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
