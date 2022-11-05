<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AchievmentsTypeDataFix extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $achs = \App\Achievement::where('achievement_type', "")->get();

        echo $achs->count() . " achievements with empty achievement type";

        foreach ($achs as $ach) 
        {
            $ach->achievement_type = "IMMEDIATE";
            $ach->save();
        }

        $achs = \App\Achievement::where('achievement_type', "")->get();

        echo $achs->count() . " achievements with empty achievement type";
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
