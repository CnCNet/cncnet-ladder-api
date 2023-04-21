<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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

        # add image path to all map objects
        \App\Map::chunk(500, function ($maps)
        {
            foreach ($maps as $map)
            {
                if ($map->ladder_id == 0)
                    continue;
                $game = $map->ladder->game;
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
