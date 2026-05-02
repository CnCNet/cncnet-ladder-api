<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to avoid doctrine/dbal dependency for column modification
        DB::statement('ALTER TABLE qm_canceled_matches MODIFY player_id INT UNSIGNED NULL');

        Schema::table('qm_canceled_matches', function (Blueprint $table) {
            // Denormalized data from qm_matches and qm_match_players
            $table->string('map_name')->nullable()->after('ladder_id');
            $table->text('canceled_by_usernames')->nullable()->after('map_name')->comment('Comma-separated list of usernames who canceled');
            $table->text('affected_player_usernames')->nullable()->after('canceled_by_usernames')->comment('Comma-separated list of affected player usernames');

            // Store player data as JSON: [{username, color}, ...]
            $table->json('player_data')->nullable()->after('affected_player_usernames')->comment('JSON array of player objects with username and color');

            // Track reason for cancellation
            $table->enum('reason', ['player_canceled', 'failed_launch'])->default('player_canceled')->after('player_data');

            // Add indexes for query performance
            $table->index(['ladder_id', 'created_at'], 'idx_ladder_created');
            $table->index(['qm_match_id', 'reason'], 'idx_match_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qm_canceled_matches', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_ladder_created');
            $table->dropIndex('idx_match_reason');

            // Drop the new columns
            $table->dropColumn(['map_name', 'canceled_by_usernames', 'affected_player_usernames', 'player_data', 'reason']);
        });

        // Revert player_id to NOT NULL using raw SQL
        DB::statement('ALTER TABLE qm_canceled_matches MODIFY player_id INT UNSIGNED NOT NULL');
    }
};
