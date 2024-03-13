<?php

use Illuminate\Database\Migrations\Migration;

class FixInfantryAchievements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $infantryAchievements = \App\Models\Achievement::where('object_name', 'E2')
            ->orWhere('object_name', 'E1')
            ->orWhere('object_name', 'INB')
            ->orWhere('object_name', 'IVAN')
            ->orWhere('object_name', 'TANY')
            ->orWhere('object_name', 'GHOST')
            ->orWhere('object_name', 'BORIS')
            ->orWhere('object_name', 'VIRUS')
            ->orWhere('object_name', 'SNIPE')
            ->orWhere('object_name', 'YURI')
            ->orWhere('object_name', 'YURIPR')
            ->orWhere('object_name', 'SHK')
            ->orWhere('object_name', 'FLKT')
            ->orWhere('object_name', 'ADOG')
            ->orWhere('object_name', 'DOG')
            ->orWhere('object_name', 'DESO')
            ->orWhere('object_name', 'JUMPJET')
            ->orWhere('object_name', 'GGI')
            ->orWhere('object_name', 'TERROR')
            ->orWhere('object_name', 'CLEG')
            ->get();

        foreach ($infantryAchievements as $infantryAchievement)
        {
            $infantryAchievement->heap_name = "INB";
            $infantryAchievement->save();
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
