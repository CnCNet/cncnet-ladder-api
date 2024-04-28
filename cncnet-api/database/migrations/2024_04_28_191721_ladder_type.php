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
        Schema::table('ladders', function (Blueprint $table) {
            $table->enum('ladder_type', ['1vs1', '2vs2', 'clan_match'])->default('1vs1');
        });

        $ladders = \App\Models\Ladder::all();

        foreach ($ladders as $ladder)
        {
            if ($ladder->clans_allowed)
            {
                $ladder->ladder_type = 'clan_match';
                $ladder->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
