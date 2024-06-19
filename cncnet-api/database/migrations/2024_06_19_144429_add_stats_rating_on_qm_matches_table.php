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
        Schema::table('qm_matches', function(Blueprint $table) {
            $table->integer('stats_teams_elo_diff')->nullable()->after('seed');
            $table->integer('stats_elo_gap_sum')->nullable()->after('stats_teams_elo_diff');
            $table->integer('stats_match_ranking')->nullable()->after('stats_elo_gap_sum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qm_matches', function(Blueprint $table) {
            $table->dropColumn('stats_teams_elo_diff');
            $table->dropColumn('stats_elo_gap_sum');
            $table->dropColumn('stats_match_ranking');
        });
    }
};
