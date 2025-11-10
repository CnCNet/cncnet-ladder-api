<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qm_match_players', function (Blueprint $table)
        {
            $table->json('colors_pref')->nullable()->after('chosen_side');
            $table->json('colors_opponent_pref')->nullable()->after('colors_pref');
        });
    }

    public function down(): void
    {
        Schema::table('qm_match_players', function (Blueprint $table)
        {
            $table->dropColumn('colors_opponent_pref');
            $table->dropColumn('colors_pref');
        });
    }
};
