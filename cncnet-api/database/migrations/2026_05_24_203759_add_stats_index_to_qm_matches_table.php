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
        Schema::table('qm_matches', function (Blueprint $table) {
            $table->index(['ladder_id', 'created_at', 'qm_map_id'], 'idx_qm_matches_stats');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qm_matches', function (Blueprint $table) {
            $table->dropIndex('idx_qm_matches_stats');
        });
    }
};
