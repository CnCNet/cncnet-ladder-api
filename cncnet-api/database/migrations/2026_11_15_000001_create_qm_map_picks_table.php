<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: After running this migration, populate existing user preferences with:
     *   php artisan migrate:map-picks --dry-run  (preview first)
     *   php artisan migrate:map-picks             (migrate active users)
     *   php artisan migrate:map-picks --all       (migrate all users)
     */
    public function up(): void
    {
        Schema::create('qm_map_picks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('ladder_id');
            $table->unsignedInteger('qm_map_id');
            $table->integer('side_index')->comment('-3 = unset (use main faction), -2 = veto/reject, -1 = random, 0+ = faction local_id');
            $table->timestamps();

            // Unique constraint: one pick per user/ladder/map combination
            $table->unique(['user_id', 'ladder_id', 'qm_map_id'], 'unique_user_ladder_map');

            // Index for common query pattern (fetch all picks for user+ladder)
            $table->index(['user_id', 'ladder_id'], 'idx_user_ladder');

            // Foreign key constraints with CASCADE DELETE
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('ladder_id')
                ->references('id')
                ->on('ladders')
                ->onDelete('cascade');

            $table->foreign('qm_map_id')
                ->references('id')
                ->on('qm_maps')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qm_map_picks');
    }
};
