<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Console\Output\ConsoleOutput;

class DeleteOldRA2Maps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $output = new ConsoleOutput();

        $ladderId = \App\Ladder::where('abbreviation', 'ra2')->first()->id;

        $maps_count = \App\Map::where('ladder_id', $ladderId)->count();
        $output->writeln($maps_count . " total RA2 maps");

        # Played maps since 2022
        $maps_played = \App\Game::where('games.created_at', '>', '2022-01-01')
            ->join('maps', 'games.hash', '=', 'maps.hash')
            ->where('maps.ladder_id', $ladderId)
            ->select('maps.hash')
            ->distinct()
            ->get();

        # map to a list
        $maps_arr = $maps_played->map(function ($value)
        {
            return $value->hash;
        });
        $output->writeln($maps_arr->count() . " maps played since 2022");

        # All RA2 maps not played since 2022
        $ra2_maps_to_delete = \App\Map::where('ladder_id', $ladderId)
            ->whereNotIn('maps.hash', $maps_arr);
        $output->writeln($ra2_maps_to_delete->count() . " ra2 maps to delete");

        # Delete
        $ra2_maps_to_delete->delete();
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
