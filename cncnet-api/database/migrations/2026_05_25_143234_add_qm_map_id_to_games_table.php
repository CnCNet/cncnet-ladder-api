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
        Schema::table('games', function (Blueprint $table) {
            $table->unsignedBigInteger('qm_map_id')->nullable()->after('qm_match_id');
            $table->index(['ladder_history_id', 'qm_map_id'], 'idx_games_map_stats');
        });

        // Backfill qm_map_id from existing qm_matches (before they're pruned)
        \Illuminate\Support\Facades\Artisan::call('games:backfill-map-ids');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex('idx_games_map_stats');
            $table->dropColumn('qm_map_id');
        });
    }
};
