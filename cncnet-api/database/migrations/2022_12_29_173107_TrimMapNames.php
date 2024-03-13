<?php

use Illuminate\Database\Migrations\Migration;

class TrimMapNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $maps = \App\Models\Map::where('name', 'like', '% ')->get();

        foreach($maps as $map)
        {
            if ($map->name != trim($map->name))
            {
                $map->name = trim($map->name);
                $map->save();
            }
        }

        $qmMaps = \App\Models\QmMap::where('description', 'like', '% ')->get();

        foreach($qmMaps as $qmMap)
        {
            if ($qmMap->description != trim($qmMap->description))
            {
                $qmMap->description = trim($qmMap->description);
                $qmMap->save();
            }

            if ($qmMap->admin_description != trim($qmMap->admin_description))
            {
                $qmMap->admin_description = trim($qmMap->admin_description);
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
        //
    }
}
