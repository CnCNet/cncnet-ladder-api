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
        Schema::table('players', function (Blueprint $table) {
            // Optimize findMatch() - called via /api/v2/qm/{ladder}/{playerName}
            $table->index(['ladder_id', 'username'], 'idx_players_ladder_username');
            // Optimize lookups by user_id (used in multiple services)
            $table->index('user_id', 'idx_players_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            // Optimize checkUserForBans(), used every time findMatch() is called
            $table->index('ip_address_id', 'idx_users_ip_address_id');
            $table->index('primary_user_id', 'idx_users_primary_user_id');
        });

        Schema::table('game_reports', function (Blueprint $table) {
            // Optimize StatsService calls: getWinnerOfTheDay, getPlayerOfTheDay, getFactionsPlayedByPlayer
            $table->index(['valid', 'best_report', 'created_at'], 'idx_game_reports_valid_best_created');
        });

        Schema::table('qm_match_players', function (Blueprint $table) {
            // Optimize queries checking if a player is waiting in queue
            $table->index(['player_id', 'waiting'], 'idx_qm_match_players_player_waiting');
        });

        Schema::table('ip_addresses', function (Blueprint $table) {
            // Optimize lookups by IP address (used for bans / login checks)
            $table->index('address', 'idx_ip_addresses_address');
        });

        Schema::table('player_active_handles', function (Blueprint $table) {
            // Optimize getPlayerActiveHandle(), used in username-based API calls
            $table->index(['player_id', 'ladder_id', 'created_at'], 'idx_pah_player_ladder_created');
        });

        Schema::table('bans', function (Blueprint $table) {
            // Optimize ban lookups by user + expiration date
            $table->index(['user_id', 'expires'], 'idx_bans_user_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex('idx_players_ladder_username');
            $table->dropIndex('idx_players_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_ip_address_id');
            $table->dropIndex('idx_users_primary_user_id');
        });

        Schema::table('game_reports', function (Blueprint $table) {
            $table->dropIndex('idx_game_reports_valid_best_created');
        });

        Schema::table('qm_match_players', function (Blueprint $table) {
            $table->dropIndex('idx_qm_match_players_player_waiting');
        });

        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->dropIndex('idx_ip_addresses_address');
        });

        Schema::table('player_active_handles', function (Blueprint $table) {
            $table->dropIndex('idx_pah_player_ladder_created');
        });

        Schema::table('bans', function (Blueprint $table) {
            $table->dropIndex('idx_bans_user_expires');
        });
    }
};
