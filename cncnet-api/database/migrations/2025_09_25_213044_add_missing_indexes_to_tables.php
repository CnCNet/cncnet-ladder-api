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
        Schema::table('ip_address_histories', function (Blueprint $table) {
            // Optimize equality lookups: WHERE user_id=? AND ip_address_id=?
            $table->index(['user_id', 'ip_address_id'], 'idx_iah_user_ip');

            // Optimize lookups by ip_address_id alone: WHERE ip_address_id=? AND user_id != ?
            $table->index('ip_address_id', 'idx_iah_ip_address_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_address_histories', function (Blueprint $table) {
            $table->dropIndex('idx_iah_user_ip');
            $table->dropIndex('idx_iah_ip_address_id');
        });
    }
};
