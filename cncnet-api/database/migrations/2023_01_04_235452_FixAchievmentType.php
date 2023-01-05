<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixAchievmentType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $achievements = \App\Achievement::where('achievement_type', '')->get();

        foreach ($achievements as $achievement)
        {
            $achievement->achievement_type = 'IMMEDIATE';
            $achievement->save();
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
