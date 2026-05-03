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
        Schema::table('bans', function (Blueprint $table) {
            // Allow NULL for expires column so cooldown bans can remain unstarted
            $table->timestamp('expires')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bans', function (Blueprint $table) {
            // Revert to NOT NULL with default '0000-00-00 00:00:00'
            $table->timestamp('expires')->nullable(false)->default('0000-00-00 00:00:00')->change();
        });
    }
};
