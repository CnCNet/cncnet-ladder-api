<?php

use App\Models\QmLadderRules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-ladder replay setting: off, on with downloads limited to testers and staff, or on with
     * downloads open to everyone. Defaults to off until a ladder opts in.
     */
    public function up(): void
    {
        Schema::table('qm_ladder_rules', function (Blueprint $table)
        {
            $table->unsignedTinyInteger('replays')
                ->default(QmLadderRules::REPLAYS_DISABLED)
                ->after('show_map_preview');
        });
    }

    public function down(): void
    {
        Schema::table('qm_ladder_rules', function (Blueprint $table)
        {
            $table->dropColumn('replays');
        });
    }
};
