<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UseLadderIdForSpawnOptionValues extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('spawn_option_values', function(Blueprint $table)
        {
            //
            $table->integer('ladder_id')->nullable();
        });

        foreach(\App\Models\SpawnOptionValue::all() as $sov)
        {
            $sov->ladder_id = $sov->qmLadderRules ? $sov->qmLadderRules->ladder->id : 0;
            $sov->save();
        }

        Schema::table('spawn_option_values', function(Blueprint $table)
        {
            //
            $table->dropColumn('qm_ladder_rules_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('spawn_option_values', function(Blueprint $table)
        {
            //
            $table->integer('qm_ladder_rules_id')->nullable();
        });

        foreach(\App\Models\SpawnOptionValue::all() as $sov)
        {
            $sov->qm_ladder_rules_id = $sov->ladder !== null ? $sov->ladder->qmLadderRules->id : null;
            $sov->save();
        }

        Schema::table('spawn_option_values', function(Blueprint $table)
        {
            //
            $table->dropColumn('ladder_id');
        });
    }
}
