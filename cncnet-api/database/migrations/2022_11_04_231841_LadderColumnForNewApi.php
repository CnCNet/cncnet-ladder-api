<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LadderColumnForNewApi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("ladders", function (Blueprint $table)
        {
            $table->tinyInteger("is_migrated_to_new_client")->default(0);
        });

        //migrate all ladders living inside of YR client
        $migrate_ladders = ['yr', 'ra2', 'sfj', 'blitz', 'ra2-test', 'yr-test'];

        foreach ($migrate_ladders as $abbreviation)
        {
            $ladder = \App\Ladder::where('abbreviation', $abbreviation)->first();
            $ladder->is_migrated_to_new_client = 1;
            $ladder->save();
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
