<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PopulateMapTiers extends Migration
{
    /**
     * Populate map tier for all active ladders.
     *
     * @return void
     */
    public function up()
    {
        $ladders = \App\Ladder::all();

        foreach ($ladders as $ladder)
        {
            $mapPool = $ladder->mapPool;

            if ($mapPool)
            {
                $ladderRules = $ladder->qmLadderRules;

                $tier1 = \App\MapTier::where('tier', 1)->where('map_pool_id', $mapPool->id)->first();

                if (!$tier1 || $tier1 == null)
                {
                    $mapTier = new \App\MapTier();
                    $mapTier->map_pool_id = $mapPool->id;
                    $mapTier->name = 'Tier 1';
                    $mapTier->tier = 1;
                    $mapTier->max_vetoes = $ladderRules->map_vetoes;
                    $mapTier->save();
                }
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
        //
    }
}
