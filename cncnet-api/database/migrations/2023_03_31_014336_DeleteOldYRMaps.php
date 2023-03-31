<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Console\Output\ConsoleOutput;

class DeleteOldYRMaps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $output = new ConsoleOutput();

        $ladderId = \App\Ladder::where('abbreviation', 'yr')->first()->id;

        # Total YR Maps
        $maps_count = \App\Map::where('ladder_id', $ladderId)->count();
        $output->writeln($maps_count . " total YR maps");

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

        # All YR maps not played since 2022
        $yr_maps_to_delete = \App\Map::where('ladder_id', $ladderId)
            ->whereNotIn('maps.hash', $maps_arr);
        $output->writeln($yr_maps_to_delete->count() . " yr maps to delete");

        # Delete
        $yr_maps_to_delete->delete();
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
