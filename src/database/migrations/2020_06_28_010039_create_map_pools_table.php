<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapPoolsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_pools', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('qm_ladder_rules_id');
            $table->text('name');
            $table->timestamps();
        });
        Schema::table('qm_maps', function(Blueprint $table)
        {
            $table->integer('map_pool_id');
        });

        Schema::table('qm_ladder_rules', function(Blueprint $table)
        {
            $table->integer('map_pool_id');
        });

        $qmLadderRules = \App\QmLadderRules::all();

        foreach ($qmLadderRules as $qm)
        {
            $pool = new \App\MapPool;
            $pool->qm_ladder_rules_id = $qm->id;
            $pool->name = "June Map Pool";
            $pool->save();

            $qm->map_pool_id = $pool->id;
            $qm->save();

            $qmMaps = $qm->ladder->qmMaps;

            foreach ($qmMaps as $qmMap)
            {
                $qmMap->map_pool_id = $pool->id;
                $qmMap->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('map_pools');
        Schema::table('qm_maps', function(Blueprint $table)
        {
            $table->dropColumn('map_pool_id');
        });

        Schema::table('qm_ladder_rules', function(Blueprint $table)
        {
            $table->dropColumn('map_pool_id');
        });
    }
}
