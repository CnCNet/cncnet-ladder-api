<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\MapPool;
use \App\Models\Map;


return new class extends Migration
{
    /**
     * Delete unused RA2 maps and/or deprecated map versions.
     */
    public function up(): void
    {
        $ra2MapPools = MapPool::where('ladder_id', 5)
            ->where('updated_at', '>', '2023-01-01')
            ->get();

        echo "Recent recent RA2 pools: " . strval(count($ra2MapPools)) . "\n";

        $mapIdsInAPool = [];

        foreach ($ra2MapPools as $ra2MapPool)
        {
            $qmMaps = $ra2MapPool->maps;

            foreach ($qmMaps as $qmMap)
            {
                if (!in_array($qmMap->map->id, $mapIdsInAPool))
                {
                    $mapIdsInAPool[] = $qmMap->map->id;
                }
            }
        }

        $allRa2Maps = Map::where('ladder_id', 5)->get();

        $deleteMapIds = [];

        echo "Maps found in recent RA2 pools: " . strval(count($mapIdsInAPool)) . "\n";

        echo "Total RA2 Maps: " . strval(count($allRa2Maps)) . "\n";

        foreach ($allRa2Maps as $ra2Map)
        {
            if (!in_array($ra2Map->id, $mapIdsInAPool))
            {
                echo "Deleting map: '" . $ra2Map->name . "', hash=" . $ra2Map->hash . "\n";
                $deleteMapIds[] = $ra2Map->id;
            }
        }

        echo "Deleting RA2 Maps: " . strval(count($deleteMapIds)) . "\n";

        foreach ($deleteMapIds as $mapId)
        {
            Map::find($mapId)->delete();
        }

        echo "Total RA2 Maps remaining: " . strval(Map::where('ladder_id', 5)->count()) . "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
