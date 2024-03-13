<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class RootMapPoolsAtLadderId extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_pools', function(Blueprint $table)
        {
            //
            $table->integer('ladder_id');
        });

        Schema::table('ladders', function(Blueprint $table)
        {
            //
            $table->integer('map_pool_id')->nullable();
        });

        foreach(\App\Models\MapPool::all() as $pool)
        {
            $pool->ladder_id = $pool->qmLadderRules->ladder->id;
            $pool->save();
        }

        foreach(\App\Models\QmLadderRules::all() as $rule)
        {
            $ladder = $rule->ladder;
            $ladder->map_pool_id = $rule->map_pool_id;
            $ladder->save();
        }

        Schema::table('map_pools', function(Blueprint $table)
        {
            //
            $table->dropColumn('qm_ladder_rules_id');
        });

        Schema::table('qm_ladder_rules', function(Blueprint $table)
        {
            //
            $table->dropColumn('map_pool_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_pools', function(Blueprint $table)
        {
            //
            $table->integer('qm_ladder_rules_id');
        });

        Schema::table('qm_ladder_rules', function(Blueprint $table)
        {
            //
            $table->integer('map_pool_id');
        });

        foreach(\App\Models\MapPool::all() as $pool)
        {
            $pool->qm_ladder_rules_id = $pool->ladder->qmLadderRules->id;
            $pool->save();
        }

        foreach(\App\Models\QmLadderRules::all() as $rule)
        {
            $ladder = $rule->ladder;
            $rule->map_pool_id = $ladder->map_pool_id;
            $rule->save();
        }

        Schema::table('map_pools', function(Blueprint $table)
        {
            //
            $table->dropColumn('ladder_id');
        });

        Schema::table('ladders', function(Blueprint $table)
        {
            //
            $table->dropColumn('map_pool_id');
        });
    }
}
