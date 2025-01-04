<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\SpawnOptionType;
use \App\Models\SpawnOption;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SpawnOption::makeOn(
            SpawnOptionType::SPAWNMAP_INI, 'PsychicDominatorSpecial.RechargeTime', 'RechargeTime', '0PsychicDominatorSpecial'
        )->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
