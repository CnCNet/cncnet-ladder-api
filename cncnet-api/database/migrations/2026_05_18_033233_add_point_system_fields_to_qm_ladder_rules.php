<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qm_ladder_rules', function (Blueprint $table)
        {
            $table->integer('upset_k')->default(20)->after('wol_k');
            $table->double('upset_k_loser_multiplier')->default(0.25)->after('upset_k');
            $table->integer('fixed_points')->default(12)->after('upset_k_loser_multiplier');
            $table->boolean('no_negative_points')->default(true)->after('fixed_points');
        });
    }

    public function down(): void
    {
        Schema::table('qm_ladder_rules', function (Blueprint $table)
        {
            $table->dropColumn('no_negative_points');
            $table->dropColumn('fixed_points');
            $table->dropColumn('upset_k_loser_multiplier');
            $table->dropColumn('upset_k');
        });
    }
};
