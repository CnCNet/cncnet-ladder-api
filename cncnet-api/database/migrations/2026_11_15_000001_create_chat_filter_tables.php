<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flags on in-game chat for staff to review: lines with a word from the staff's list, and senders
     * who flooded the chat or kept repeating one line. Read from the chat merged out of every replay
     * of a game (ReplayService::chat), so a flag is kept when the replay files are evicted.
     */
    public function up(): void
    {
        // The words staff want flagged, matched as whole words or anywhere in a word.
        Schema::create('chat_filter_terms', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('term', 100);
            $table->boolean('whole_word')->default(true);
            $table->timestamps();
        });

        // One row: how much chat from one sender counts as spam.
        Schema::create('chat_filter_settings', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('burst_messages')->unsigned();
            $table->integer('burst_seconds')->unsigned();
            $table->integer('repeat_messages')->unsigned();
            $table->integer('repeat_seconds')->unsigned();
            $table->timestamps();
        });

        Schema::create('game_chat_flags', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('game_id')->unsigned();
            $table->integer('ladder_id')->unsigned()->nullable();

            // word, burst or repeat.
            $table->string('reason', 16);

            // The sender as the game named them, and where in the game: the flagged line, or the first
            // line of the burst or repeats.
            $table->integer('house');
            $table->string('name', 64);
            $table->integer('frame');
            $table->integer('seconds')->unsigned();

            // The matched words, or how many lines in how long; and the line or lines themselves.
            $table->string('detail', 255);
            $table->text('text');

            // open, reviewed or dismissed. Re-scanning keeps a reviewed or dismissed flag's status.
            $table->string('status', 16)->default('open');
            $table->integer('reviewed_by')->unsigned()->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->unique(['game_id', 'reason', 'house', 'frame']);
            $table->index(['status', 'ladder_id']);
            $table->foreign('game_id')->references('id')->on('games')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_chat_flags');
        Schema::dropIfExists('chat_filter_settings');
        Schema::dropIfExists('chat_filter_terms');
    }
};
