<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\Ladder;
use \App\Models\Side;
use \App\Models\QmLadderRules;
use \App\Models\SpawnOptionValue;
use \App\Models\MapPool;
use \App\Models\Map;
use \App\Models\QmMap;

return new class extends Migration
{
    /**
     * Create RA2 2v2 ladder.
     */
    public function up(): void
    {
        $ra2Ladder = Ladder::where('abbreviation', 'ra2-cl')->first();

        #create test ladder
        $ra2Ladder2v2Ladder = $ra2Ladder->replicate()->fill([
            'name' => 'RA2 2v2 Ladder',
            'abbreviation' => 'ra2-2v2',
            'ladder_type' => Ladder::TWO_VS_TWO,
            'private' => true
        ]);
        $ra2Ladder2v2Ladder->save();

        $lc = new \App\Http\Controllers\LadderController;
        $lc->addLadder($ra2Ladder2v2Ladder->id); #create ladder histories

        #add sides
        $sides = Side::where('ladder_id', $ra2Ladder->id)->get();
        for ($i = 0; $i < count($sides); ++$i)
        {
            $side = new Side();
            $side->ladder_id = $ra2Ladder2v2Ladder->id;
            $side->local_id = $sides[$i]->local_id;
            $side->name = $sides[$i]->name;
            $side->save();
        }

        #create ladder rules			
        $ra2LadderRules = QmLadderRules::where('ladder_id', $ra2Ladder->id)->first();
        $newLadderRules = $ra2LadderRules->replicate()->fill([
            'ladder_id' => $ra2Ladder2v2Ladder->id,
            'player_count' => 4
        ]);
        $newLadderRules->save();

        #Copy over the RA2 spawn options
        $options = SpawnOptionValue::where('ladder_id', $ra2Ladder->id)->get();

        foreach ($options as $option)
        {
            $o = new SpawnOptionValue;
            $o->ladder_id = $ra2Ladder2v2Ladder->id;
            $o->spawn_option_id = $option->spawn_option_id;
            $o->value_id = $option->value_id;
            $o->save();
        }

        #Copy over existing RA2 ladder Map Pool
        $newPool = new MapPool;
        $newPool->name = 'RA2 2v2 Map Pool';
        $newPool->ladder_id = $ra2Ladder2v2Ladder->id;
        $newPool->save();

        # copy map tiers
        foreach ($ra2Ladder->mapPool->tiers as $mapTier)
        {
            $newMapTier = $mapTier->replicate()->fill([
                'map_pool_id' => $mapTier->map_pool_id
            ]);
            $newMapTier->save();
        }

        #copy over maps
        $ra2Maps = Map::where('ladder_id', $ra2Ladder->id)->get();
        foreach ($ra2Maps as $ra2Map)
        {
            if (!$ra2Map->is_active)
                continue;

            $newMap = $ra2Map->replicate()->fill([
                'ladder_id' => $ra2Ladder2v2Ladder->id
            ]);
            $newMap->save();
        }

        #copy ra2 qm maps
        $ra2QmMaps = QmMap::where('map_pool_id', $ra2Ladder->map_pool_id)->get();
        foreach ($ra2QmMaps as $ra2QmMap)
        {
            $map_id = Map::where('ladder_id', $ra2Ladder2v2Ladder->id)
                ->where('hash', $ra2QmMap->map->hash)
                ->first()->id;

            $newQmMap = $ra2QmMap->replicate()->fill([
                'ladder_id' => $ra2Ladder2v2Ladder->id,
                'map_pool_id' => $newPool->id,
                'map_id' => $map_id
            ]);
            $newQmMap->valid = 1;
            $newQmMap->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};