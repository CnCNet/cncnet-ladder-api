<?php

use Illuminate\Database\Migrations\Migration;

class PopulateMapSpawnCount extends Migration
{
    /**
     * Populate map spawn_count values
     *
     * @return void
     */
    public function up()
    {
        $maps = \App\Models\Map::all();

        foreach ($maps as $map)
        {
            $mapHeaders = $map->mapHeaders();

            if ($mapHeaders == null || $mapHeaders->count() == 0)
                continue;

            $map->spawn_count = $mapHeaders->first()->numStartingPoints;
            $map->save();
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
