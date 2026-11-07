<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Statistics read out of a game's replay, one row per game.
     *
     * Every player's recording holds the same simulation, so the first replay uploaded for a game
     * is enough. The row outlives the replay file itself, which the storage budget may evict.
     */
    public function up(): void
    {
        Schema::create('game_replay_stats', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('game_id')->unsigned()->unique();

            // The replay the stats were read from; null once that replay row is gone.
            $table->integer('game_replay_id')->unsigned()->nullable();

            // ReplayStatsBuilder::VERSION the stats were built with, so older ones can be rebuilt.
            $table->unsignedSmallInteger('version');

            $table->longText('stats');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_replay_stats');
    }
};
