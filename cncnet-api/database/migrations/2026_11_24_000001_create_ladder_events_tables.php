<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ladder events (e.g. the RA2 2v2 holiday championship).
 * Award rules live in config/ladder_events.php; these tables hold the mutable data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ladder_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug', 64)->unique();
            $table->unsignedInteger('ladder_id');
            $table->enum('status_override', ['final'])->nullable();
            $table->longText('computed_payload')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->longText('frozen_payload')->nullable();
            $table->timestamp('finalised_at')->nullable();
            $table->unsignedInteger('finalised_by')->nullable();
            $table->timestamps();
        });

        Schema::create('ladder_event_group_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ladder_event_id');
            $table->string('group_key', 48);
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->unique(['ladder_event_id', 'group_key', 'user_id'], 'ladder_event_group_member_unique');
            $table->foreign('ladder_event_id')->references('id')->on('ladder_events')->cascadeOnDelete();
        });

        Schema::create('ladder_event_award_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ladder_event_id');
            $table->string('award_key', 48);
            $table->enum('kind', ['manual', 'override']);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('game_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('set_by');
            $table->timestamps();

            $table->unique(['ladder_event_id', 'award_key', 'kind'], 'ladder_event_award_result_unique');
            $table->foreign('ladder_event_id')->references('id')->on('ladder_events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ladder_event_award_results');
        Schema::dropIfExists('ladder_event_group_members');
        Schema::dropIfExists('ladder_events');
    }
};
