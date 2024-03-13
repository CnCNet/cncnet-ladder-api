<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class MapTablePerLadder extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maps_backup', function(Blueprint $table)
            {
                $table->integer('id');
                $table->string('hash')->nullable();
                $table->string('name')->nullable();
            });

        DB::statement('INSERT INTO maps_backup SELECT * from maps;');

	Schema::table('maps', function(Blueprint $table)
	    {
		//
                $table->integer('ladder_id')->nullable();
	    });

        $currentMaps = \App\Models\Map::all();

        foreach ($currentMaps as $map)
        {
            $qmMap = \App\Models\QmMap::where('map_id', '=', $map->id)->first();

            if ($qmMap !== null)
            {
                $map->ladder_id = $qmMap->ladder_id;
            }

            $game = \App\Models\Game::where('hash', '=', $map->hash)->first();

            if ($game !== null)
            {
                $map->ladder_id = $game->ladderHistory->ladder_id;
            }

            $map->save();

            if ($map->ladder_id === null)
            {
                $map->delete();
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
	Schema::table('maps', function(Blueprint $table)
	    {
	        //
                $table->dropColumn('ladder_id');
	    });

        DB::statement('DELETE FROM maps');
        DB::statement('INSERT INTO maps SELECT * from maps_backup');

        Schema::drop('maps_backup');
    }

}
