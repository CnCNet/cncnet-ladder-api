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
        Schema::table('player_game_reports', function (Blueprint $table) {
           $table->index('created_at', 'player_game_reports_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_game_reports', function (Blueprint $table) {
           $table->dropIndex('player_game_reports_created_at_index');
        });
    }
};
