<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePlayerGameReportsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('player_game_reports', function(Blueprint $table)
        {
			$table->increments('id');
            $table->integer('game_id')->unsigned();
            $table->integer('game_report_id')->unsigned();
            $table->integer('player_id')->unsigned();
            $table->integer('local_id');
            $table->integer('local_team_id');
            $table->integer('points')->default(0);
            $table->integer('stats_id')->nullable();
            $table->boolean('disconnected')->default(false);
            $table->boolean('no_completion')->default(false);
            $table->boolean('quit')->default(false);
            $table->boolean('won')->default(false);
            $table->boolean('defeated')->default(false);
            $table->boolean('draw')->default(false);
            $table->boolean('spectator')->default(false);
            $table->timestamps();
        });

        $local_id = 0;
        $playerPoints = \App\PlayerPoint::all();
        foreach ($playerPoints as $playerPoints)
        {
            $game = $playerPoints->game()->first();

            if ($game === null)
                continue;

            $gameReport = \App\Models\GameReport::where('game_id', $game->id)->first();

            if ($gameReport == null)
            {
                $gameReport = new \App\Models\GameReport;
                $gameReport->game_id = $game->id;
                $gameReport->player_id = $playerPoints->player_id;
                $gameReport->best_report = true;
                $gameReport->manual_report = true;
                $gameReport->duration = $game->dura;
                $gameReport->fps = $game->afps;
                $gameReport->valid = true;
                $gameReport->oos = $game->oosy;
                $gameReport->created_at = $game->created_at;
                $gameReport->save();
                $game->save();
                $local_id = 0;
            }
            else {
                $local_id++;
            }

            $playerGR = new \App\Models\PlayerGameReport;
            $playerGR->game_report_id = $gameReport->id;
            $playerGR->game_id = $playerPoints->game_id;
            $playerGR->player_id = $playerPoints->player_id;
            $playerGR->local_id = $local_id;
            $playerGR->local_team_id = $local_id;
            $playerGR->points = $playerPoints->points_awarded;

            $gameStats = \App\GameStats::where('player_id', $playerGR->player_id)
                                   ->where('game_id', $game->id)
                                   ->first();

            if ($gameStats !== null)
            {
                $playerGR->stats_id = $gameStats->stats_id;
            }

            $playerGR->disconnected = $game->sdfx;

            $playerGR->no_completion = false;
            $playerGR->quit = false;
            $playerGR->won = $playerPoints->game_won;
            $playerGR->defeated = !$playerPoints->game_won;
            $playerGR->draw = false;
            $playerGR->spectator = false;
            $playerGR->created_at = $game->created_at;
            $playerGR->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('player_game_reports');
    }
}
