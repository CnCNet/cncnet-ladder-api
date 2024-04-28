<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\Map;
use \App\Models\MapTier;
use \App\Models\QmMap;
use \App\Models\MapPool;
use \App\Models\Ladder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mapPool = MapPool::find('91');

        if(!isset($mapPool)) {
            return;
        }

        $testLadder = Ladder::where('abbreviation', 'ra2-test')->first();

        $newPool = new MapPool();
        $newPool->ladder_id = $testLadder->id;
        $newPool->name = $mapPool->name . " new 3";
        $newPool->save();

        foreach ($mapPool->tiers as $tier) 
        {
            $newTier = new MapTier();
            $newTier->tier = $tier->tier;
            $newTier->name = $tier->name;
            $newTier->max_vetoes = $tier->max_vetoes;
            $newTier->map_pool_id = $newPool->id;
            $newTier->save();
        }

        foreach ($mapPool->maps as $qmMap) 
        {
            $newMap = new Map();
            $newMap->ladder_id = $testLadder->id;
            $newMap->hash = $qmMap->map->hash;
            $newMap->name = $qmMap->map->name;
            $newMap->image_hash = $qmMap->map->image_hash;
            $newMap->image_path = $qmMap->map->image_path;
            $newMap->save();

            $newQmMap = new QmMap();
            $newQmMap->ladder_id = $testLadder->id;
            $newQmMap->map_id = $newMap->id;
            $newQmMap->description = $qmMap->description;
            $newQmMap->bit_idx = $qmMap->bit_idx;
            $newQmMap->valid = $qmMap->valid;
            $newQmMap->spawn_order = $qmMap->spawn_order;
            $newQmMap->team1_spawn_order = $qmMap->team1_spawn_order;
            $newQmMap->team2_spawn_order = $qmMap->team2_spawn_order;
            $newQmMap->allowed_sides = $qmMap->allowed_sides;
            $newQmMap->admin_description = $qmMap->admin_description;
            $newQmMap->map_pool_id = $newPool->id;
            $newQmMap->rejectable = $qmMap->rejectable;
            $newQmMap->default_reject = $qmMap->default_reject;
            $newQmMap->random_spawns = $qmMap->random_spawns;
            $newQmMap->map_tier = $qmMap->map_tier;
            $newQmMap->weight = $qmMap->weight;
            $newQmMap->save();
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
