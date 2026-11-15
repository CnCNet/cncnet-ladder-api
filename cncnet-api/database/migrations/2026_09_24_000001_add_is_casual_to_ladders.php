<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Casual ladders only accept casual matchmaking requests and ranked ladders only ranked ones,
     * so casual and ranked players never share a queue, match or ladder history.
     */
    public function up(): void
    {
        Schema::table('ladders', function (Blueprint $table) {
            $table->boolean('is_casual')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('ladders', function (Blueprint $table) {
            $table->dropColumn('is_casual');
        });
    }
};
