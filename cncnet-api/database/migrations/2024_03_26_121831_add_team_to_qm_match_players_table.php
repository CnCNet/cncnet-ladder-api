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
            $table->string('team')->nullable()->after('clan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qm_match_players', function (Blueprint $table) {
            $table->dropColumn('team');
        });
    }
};
