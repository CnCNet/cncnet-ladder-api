<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class ReformatGamesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('games_backup', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('ladder_history_id')->unsigned();
            $table->integer('wol_game_id')->unsigned();
            $table->integer('afps');
            $table->boolean('oosy');
            $table->integer('bamr');
            $table->timestamps();
            $table->integer('crat');
            $table->longText('dura');
            $table->longText('cred');
            $table->integer('shrt');
            $table->integer('supr');
            $table->integer('unit');
            $table->integer('plrs');
            $table->string('scen');
            $table->string('hash');
            $table->boolean('sdfx');
        });

        DB::statement('INSERT INTO games_backup SELECT * from games');
        Schema::table('games', function(Blueprint $table)
        {
            //
            $table->dropColumn('afps');
            $table->dropColumn('dura');
            $table->dropColumn('sdfx');
            $table->dropColumn('oosy');
            $table->integer('game_report_id')->nullable()->unsigned();
        });

        $games = \App\Models\Game::all();
        foreach ($games as $game)
        {
            $gr = \App\Models\GameReport::where('game_id', $game->id)->first();
            if ($gr !== null)
            {
                $game->game_report_id = $gr->id;
                $game->save();
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
        //Schema::rename('games', 'games_backup_backup');
        Schema::drop('games');
        Schema::rename('games_backup', 'games');
    }
}
