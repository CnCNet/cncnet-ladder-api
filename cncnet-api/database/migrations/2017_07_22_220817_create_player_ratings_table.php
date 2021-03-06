<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerRatingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('player_ratings', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('player_id');
            $table->integer('rating');
            $table->integer('peak_rating');
            $table->integer('rated_games');
			$table->timestamps();
		});
        $players = \App\Player::all();

        foreach ($players as $player)
        {
            $prating = new \App\PlayerRating();
            $prating->player_id = $player['id'];
            //$prating->rating = 1200;
            //$prating->peak_rating = 0;
            //$prating->rated_games = 0;
            $prating->save();
        }
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('player_ratings');
	}

}
