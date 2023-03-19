<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixAchievements extends Migration
{
    /**
     * Fix initiates
     *
     * @return void
     */
    public function up()
    {
        $infantryAchievements = \App\Achievement::where('object_name', 'FLAKT')
            ->orWhere('object_name', 'INIT')
            ->orWhere('object_name', 'BRUTE')
            ->get();

        foreach ($infantryAchievements as $infantryAchievement)
        {
            $infantryAchievement->heap_name = "INB";
            $infantryAchievement->save();
        }

        $aircraftAchievements = \App\Achievement::where('object_name', 'ORCA')
            ->orWhere('object_name', 'BEAG')
            ->get();

        foreach ($aircraftAchievements as $aircraftAchievement)
        {
            $aircraftAchievement->heap_name = "PLB";
            $aircraftAchievement->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
