<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_clips', function (Blueprint $table)
        {
            $table->id();
            $table->string("clip_filename");

            $table->unsignedInteger('player_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('game_id');

            $table->foreign('player_id')->references('id')->on('players');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('game_id')->references('id')->on('games');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_clips');
    }
};
