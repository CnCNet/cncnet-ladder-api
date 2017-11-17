<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Log;

class CreateGameObjectCountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('game_object_counts', function(Blueprint $table)
		{
			$table->bigIncrements('id');
            $table->integer('stats_id')->unsigned()->index();
            $table->integer('countable_game_objects_id')->unsigned();
            $table->integer('count');
		});

		Schema::create('stats2', function(Blueprint $table)
		{
			//
            $table->integer('id')->unsigned()->index();
            $table->integer('sid')->nullable();
            $table->integer('col')->nullable();
            $table->integer('cty')->nullable();
            $table->integer('crd')->nullable();
            $table->integer('hrv')->nullable();
            $table->integer('player_game_report_id')->unsigned()->index();
		});

        \App\PlayerGameReport::chunk(500, function($pgrs) {
            foreach ($pgrs as $pgr)
            {
                $heaps = array("CRA","BLC","BLK","PLK","UNK","INK","BLL","PLL","UNL","INL","BLB",
                               "PLB","UNB","INB","VSK","VSL","VSB");
                $stats = \App\Stats::find($pgr->stats_id);
                $ladder_id = $pgr->game->ladderHistory->ladder_id;
                if ($stats !== null)
                {
                    $stats2 = new \App\Stats2;
                    $stats2->id = $stats->id;
                    $stats2->sid = json_decode($stats->sid !== null ? $stats->sid :'{"value": null}')->value;
                    $stats2->sid = json_decode($stats->col !== null ? $stats->col :'{"value": null}')->value;
                    $stats2->cty = json_decode($stats->cty !== null ? $stats->cty :'{"value": null}')->value;
                    $stats2->crd = json_decode($stats->crd !== null ? $stats->crd :'{"value": null}')->value;
                    $stats2->hrv = json_decode($stats->hrv !== null ? $stats->hrv :'{"value": null}')->value;
                    $stats2->player_game_report_id = $pgr->id;
                    $stats2->save();

                    $stats_array = $stats->toArray();
                    foreach ($stats_array as $col_ => $val)
                    {
                        $col = strtoupper($col_);
                        if (!in_array($col, $heaps))
                            continue;
                        if ($val === null)
                            continue;
                        if ($col === null || is_numeric($col))
                            continue;

                        $objects = json_decode($val)->counts;

                        foreach ($objects as $heap_name => $count)
                        {

                            if ($count == 0)
                                continue;

                            $cgo = \App\CountableGameObject::where('heap_name', '=', $col)
                                                           ->where('name', '=', $heap_name)
                                                           ->where('ladder_id', '=', $ladder_id)->first();
                            if ($cgo !== null)
                            {
                                $goc = new \App\GameObjectCounts;
                                $goc->stats_id = $stats->id;
                                $goc->countable_game_objects_id = $cgo->id;
                                $goc->count = $count;
                                $goc->save();
                            }
                            else {
                                //var_dump($objects);
                                //error_log("$ladder_id, $col => $heap_name");
                            }
                        }
                    }
                }
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
		Schema::drop('game_object_counts');
        Schema::drop('stats2');
	}

}
