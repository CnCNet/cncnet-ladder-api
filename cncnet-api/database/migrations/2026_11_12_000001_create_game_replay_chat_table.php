<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The in-game chat each uploaded replay recorded, one row per player per game.
     *
     * Each copy holds only what its player sent and received - their own team's messages, and nothing
     * after they left - so the game page merges every copy's rows into the one conversation (see
     * ReplayChat). Like the seals, the rows are kept when the storage budget evicts the files.
     */
    public function up(): void
    {
        Schema::create('game_replay_chat', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('game_id')->unsigned();
            $table->integer('player_id')->unsigned();

            // ReplayChat::VERSION when read, so a change to what is stored can read the files again.
            $table->integer('version')->unsigned();

            // The lines as ReplayChat::read gives them, in the order the copy recorded them.
            $table->mediumText('messages');

            $table->timestamps();

            $table->unique(['game_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_replay_chat');
    }
};
