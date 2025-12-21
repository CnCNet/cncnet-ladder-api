<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SpawnOption;
use App\Models\SpawnOptionType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SpawnOption::makeOne(SpawnOptionType::SPAWN_INI, "Settings.AutoSaveInterval", "Settings", "AutoSaveInterval")->save(); // 0
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
