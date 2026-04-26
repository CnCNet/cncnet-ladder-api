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
        Schema::table('qm_match_players', function (Blueprint $table) {
            $table->boolean('twitch_live_at_start')
                ->default(false)
                ->after('is_observer')
                ->comment('Player was live on Twitch when match formed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qm_match_players', function (Blueprint $table) {
            $table->dropColumn('twitch_live_at_start');
        });
    }
};
