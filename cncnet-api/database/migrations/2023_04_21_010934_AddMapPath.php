<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Console\Output\ConsoleOutput;

class AddMapPath extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maps', function (Blueprint $table)
        {
            $table->string('image_path'); # path to image, `/maps/{$game}/{$filename}`
        });

        # Cache all ladder_ids and game attribute
        $hashmap[] = [];
        \App\Ladder::all()->map(function ($ladder) use (&$hashmap)
        {
            $hashmap[$ladder->id] = $ladder->game;
            return $hashmap;
        });

        # add image path to all map objects
        \App\Map::chunk(500, function ($maps) use (&$hashmap, &$output)
        {
            foreach ($maps as $map)
            {
                if ($map->ladder_id == 0)
                    continue;
                $game = $hashmap[$map->ladder_id];
                $hash = $map->hash;
                $map->image_path = "/maps/$game/$hash.png";
                $map->save();
            }
        });
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
