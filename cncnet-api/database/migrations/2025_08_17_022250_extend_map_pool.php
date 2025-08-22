<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_pools', function (Blueprint $table)
        {
            $table->integer('forced_faction_id')->nullable()->after('ladder_id');
            $table->decimal('forced_faction_ratio', 4, 3)->nullable()->after('forced_faction_id');
            $table->json('invalid_faction_pairs')->nullable()->after('forced_faction_ratio');
        });
    }

    public function down(): void
    {
        Schema::table('map_pools', function (Blueprint $table)
        {
            $table->dropColumn(['forced_faction_id', 'forced_faction_ratio', 'invalid_faction_pairs']);
        });
    }
};
