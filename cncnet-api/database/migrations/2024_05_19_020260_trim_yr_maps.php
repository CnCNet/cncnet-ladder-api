<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\MapPool;
use \App\Models\Map;

return new class extends Migration
{
    /**
     * Disable unused YR maps.
     */
    public function up(): void
    {
        $yrLadder = \App\Models\Ladder::where('abbreviation', 'yr')->first();

	if (!$yrLadder) {
        	echo "No ladder found with abbreviation 'yr'. Skipping trim.\n";
        	return;
    	}

        $yrMapPools = MapPool::where('ladder_id', $yrLadder->id)
            ->where('updated_at', '>', '2023-01-01')
            ->get();

        echo "Recent recent YR pools: " . strval(count($yrMapPools)) . "\n";

        $mapIdsInAPool = [];

        foreach ($yrMapPools as $yrMapPool)
        {
            $qmMaps = $yrMapPool->maps;

            foreach ($qmMaps as $qmMap)
            {
                if (!in_array($qmMap->map->id, $mapIdsInAPool))
                {
                    $mapIdsInAPool[] = $qmMap->map->id;
                }
            }
        }

        $allYrMaps = Map::where('ladder_id', $yrLadder->id)->where('is_active', true)->get();

        $disabledMapIds = [];

        echo "Maps found in recent YR pools: " . strval(count($mapIdsInAPool)) . "\n";

        echo "Total active YR Maps: " . strval(count($allYrMaps)) . "\n";

        foreach ($allYrMaps as $yrMap)
        {
            if (!in_array($yrMap->id, $mapIdsInAPool))
            {
                echo "Disabling map: '" . $yrMap->name . "', hash=" . $yrMap->hash . "\n";
                $disabledMapIds[] = $yrMap->id;
            }
        }

        echo "Disabling YR Maps: " . strval(count($disabledMapIds)) . "\n";

        foreach ($disabledMapIds as $mapId)
        {
            $map = Map::where('id', $mapId)->first();
            $map->is_active = false;
            $map->save();
        }

        echo "Total active YR Maps remaining: " . strval(Map::where('ladder_id', $yrLadder->id)->where('is_active', true)->count()) . "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
