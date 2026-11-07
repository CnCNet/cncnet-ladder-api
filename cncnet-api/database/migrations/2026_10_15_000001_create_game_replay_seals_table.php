<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The canonical seals of each uploaded replay, one row per player per game.
     *
     * Every player's recording of a game carries the same seal for every 15-second interval both
     * reached (yrpp-spawner docs/replay-format.md, "Canonical hash"), so comparing them shows whether
     * the copies are the same unedited game. The rows are kept when the storage budget evicts the
     * replay files: a few kilobytes per game is enough to check any copy someone produces later.
     */
    public function up(): void
    {
        Schema::create('game_replay_seals', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('game_id')->unsigned();
            $table->integer('player_id')->unsigned();

            // [[throughFrame, hex hash], ...] in frame order, recomputed from the file rather than
            // taken from it. The last one of a cleanly closed recording covers whatever followed the
            // final full interval.
            $table->mediumText('seals');

            // Whether the recording closed cleanly, and so whether the last seal is a final one.
            $table->boolean('complete');

            // Whether the seals the file carries are the ones its contents give. False is a file
            // edited after the game.
            $table->boolean('consistent');

            $table->timestamps();

            $table->unique(['game_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_replay_seals');
    }
};
