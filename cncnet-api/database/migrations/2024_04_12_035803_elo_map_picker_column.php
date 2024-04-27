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
        Schema::table('qm_ladder_rules', function (Blueprint $table) {
            $table->boolean('use_elo_map_picker')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       
    }
};
