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
        SpawnOption::makeOne(
            SpawnOptionType::SPAWNMAP_INI, 'LightningStormSpecial.RechargeTime', 'LightningStormSpecial', 'RechargeTime'
        )->save();

        SpawnOption::makeOne(
            SpawnOptionType::SPAWNMAP_INI, 'NukeSpecial.RechargeTime', 'NukeSpecial', 'RechargeTime'
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