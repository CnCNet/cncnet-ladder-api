<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePlayerHistoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('player_histories', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('player_id')->unsigned();
            $table->integer('ladder_history_id')->unsigned();
            $table->integer('tier')->default(1);
			$table->timestamps();
		});
        $ladder_histories = \App\Models\LadderHistory::all();

        foreach ($ladder_histories as $history)
        {
            $players = DB::table('player_game_reports as pgr')->join("games as g", 'g.id', '=', 'pgr.game_id')
                                                              ->where('g.ladder_history_id','=', $history->id)
                                                              ->select('pgr.player_id')
                                                              ->groupBy('pgr.player_id')->get();
            foreach ($players as $player)
            {
                $pHist = new \App\Models\PlayerHistory;
                $pHist->player_id = $player->player_id;
                $pHist->ladder_history_id = $history->id;
                $pHist->tier = 1;
                $pHist->save();
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
		Schema::drop('player_histories');
	}

}
