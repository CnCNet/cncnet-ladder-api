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
     * Create YR 2v2 ladder.
     */
    public function up(): void
    {
        $yrLadder = Ladder::where('abbreviation', 'yr')->first();

        #create test ladder
        $yrLadder2v2Ladder = $yrLadder->replicate()->fill([
            'name' => 'YR 2v2 Ladder',
            'abbreviation' => 'yr-2v2',
        ]);
        $yrLadder2v2Ladder->save();

        $lc = new \App\Http\Controllers\LadderController;
        $lc->addLadder($yrLadder2v2Ladder->id); #create ladder histories

        #add sides
        $sides = Side::where('ladder_id', $yrLadder->id)->get();
        for ($i = 0; $i < count($sides); ++$i)
        {
            $side = new Side();
            $side->ladder_id = $yrLadder2v2Ladder->id;
            $side->local_id = $i;
            $side->name = $sides[$i]->name;
            $side->save();
        }

        #create ladder rules			
        $yrLadderRules = QmLadderRules::where('ladder_id', $yrLadder->id)->first();
        $newLadderRules = $yrLadderRules->replicate()->fill([
            'ladder_id' => $yrLadder2v2Ladder->id,
            'player_count' => 4
        ]);
        $newLadderRules->save();

        #Copy over the YR spawn options
        $options = SpawnOptionValue::where('ladder_id', $yrLadder->id)->get();

        foreach ($options as $option)
        {
            $o = new SpawnOptionValue;
            $o->ladder_id = $yrLadder2v2Ladder->id;
            $o->spawn_option_id = $option->spawn_option_id;
            $o->value_id = $option->value_id;
            $o->save();
        }

        #Copy over existing YR ladder Map Pool
        $newPool = new MapPool;
        $newPool->name = 'YR 2v2 Map Pool';
        $newPool->ladder_id = $yrLadder2v2Ladder->id;
        $newPool->save();

        #copy over maps
        $yrMaps = Map::where('ladder_id', $yrLadder->id)->get();
        foreach ($yrMaps as $yrMap)
        {
            if (!$yrMap->is_active)
                continue;

            $newMap = $yrMap->replicate()->fill([
                'ladder_id' => $yrLadder2v2Ladder->id,
            ]);
            $newMap->save();
        }

        $yrQmMaps = QmMap::where('map_pool_id', $yrLadder->map_pool_id)->get();
        #copy yr qm maps
        foreach ($yrQmMaps as $yrQmMap)
        {
            $map_id = Map::where('ladder_id', $yrLadder->id)
                ->where('hash', $yrQmMap->map->hash)
                ->first()->id;

            $newQmMap = $yrQmMap->replicate()->fill([
                'ladder_id' => $yrLadder2v2Ladder->id,
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
