<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteSFJLadder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sfjLadder = \App\Ladder::where('abbreviation', 'sfj')->first();

        \App\QmMap::where('ladder_id', $sfjLadder->id)->delete();
        \App\Map::where('ladder_id', $sfjLadder->id)->delete();
        \App\Player::where('ladder_id', $sfjLadder->id)->delete();
        
        $ladderHistories = \App\LadderHistory::where('ladder_id', $sfjLadder->id)->get();

        foreach ($ladderHistories as $ladderHistory)
        {
            $games = \App\Game::where('ladder_history_id', $ladderHistory->id)->get();
            foreach ($games as $game)
            {
                $playerGames = \App\PlayerGameReport::where('game_id', $game->id)->get();

                foreach($playerGames as $playerGame)
                {
                    \App\Stats2::where('player_game_report_id', $playerGame->id)->delete();
                }
                \App\PlayerGameReport::where('game_id', $game->id)->delete();
                \App\GameReport::where('game_id', $game->id)->delete();
            }
            \App\Game::where('ladder_history_id', $ladderHistory->id)->delete();

            \App\PlayerCache::where('ladder_history_id', $ladderHistory->id)->delete();
            \App\PlayerHistory::where('ladder_history_id', $ladderHistory->id)->delete();
        }
        \App\LadderHistory::where('ladder_id', $sfjLadder->id)->delete();
        \App\LadderAdmin::where('ladder_id', $sfjLadder->id)->delete();
        \App\MapPool::where('ladder_id', $sfjLadder->id)->delete();
        \App\Side::where('ladder_id', $sfjLadder->id)->delete();
        \App\SpawnOptionValue::where('ladder_id', $sfjLadder->id)->delete();
        \App\QmLadderRules::where('ladder_id', $sfjLadder->id)->delete();
        \App\PlayerActiveHandle::where('ladder_id', $sfjLadder->id)->delete();

        $sfjLadder->delete();
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
