<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replays recorded by the Quick Match spawner, one file per player per game.
     *
     * Each player's file differs - the replay stores that player's viewport and unit selection -
     * so a game has as many replays as it had participants.
     */
    public function up(): void
    {
        Schema::create('game_replays', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('game_id')->unsigned();
            $table->integer('player_id')->unsigned();
            $table->integer('user_id')->unsigned();

            // Path relative to the replay disk root. Randomised, not derived from the game or
            // player, so the files cannot be enumerated if the storage root is ever exposed.
            $table->string('filename');
            $table->unsignedBigInteger('file_size');

            $table->timestamps();

            // One replay per player per game; a re-upload replaces the existing row.
            $table->unique(['game_id', 'player_id']);

            // Total-size accounting evicts oldest-first, and the game page looks replays up by game.
            $table->index('created_at');
            $table->index('game_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_replays');
    }
};
