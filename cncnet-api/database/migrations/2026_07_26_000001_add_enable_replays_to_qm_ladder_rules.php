<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Controls whether the Quick Match client records and uploads replays for a ladder.
     *
     * Defaults to false so replays stay off everywhere until a ladder explicitly opts in. This
     * doubles as the kill switch: turning it off makes the client fall back to the stock spawner
     * on the next match, with no client update required.
     */
    public function up(): void
    {
        Schema::table('qm_ladder_rules', function (Blueprint $table)
        {
            $table->boolean('enable_replays')->default(false)->after('show_map_preview');
        });
    }

    public function down(): void
    {
        Schema::table('qm_ladder_rules', function (Blueprint $table)
        {
            $table->dropColumn('enable_replays');
        });
    }
};
