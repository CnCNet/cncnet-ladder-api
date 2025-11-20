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
            $table->string('client_version', 32)->nullable()->after('ddraw_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qm_match_players', function (Blueprint $table) {
            $table->dropColumn('client_version');
        });
    }
};
