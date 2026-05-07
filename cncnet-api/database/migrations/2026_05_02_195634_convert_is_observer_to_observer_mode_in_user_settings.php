<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new observer_mode column
        Schema::table('user_settings', function (Blueprint $table) {
            $table->enum('observer_mode', ['play', 'observe_only', 'play_and_observe'])
                ->nullable()
                ->after('is_observer');
        });

        // Migrate existing data
        DB::statement("UPDATE user_settings SET observer_mode = 'observe_only' WHERE is_observer = 1");
        DB::statement("UPDATE user_settings SET observer_mode = NULL WHERE is_observer = 0");

        // Drop old column
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('is_observer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add is_observer column
        Schema::table('user_settings', function (Blueprint $table) {
            $table->tinyInteger('is_observer')->default(0)->after('match_any_map');
        });

        // Migrate data back
        DB::statement("UPDATE user_settings SET is_observer = 1 WHERE observer_mode = 'observe_only'");
        DB::statement("UPDATE user_settings SET is_observer = 0 WHERE observer_mode IS NULL OR observer_mode = 'play' OR observer_mode = 'play_and_observe'");

        // Drop new column
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('observer_mode');
        });
    }
};
