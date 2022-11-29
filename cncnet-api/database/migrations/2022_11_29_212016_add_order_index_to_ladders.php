<?php

use App\Ladder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderIndexToLadders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ladders', function (Blueprint $table)
        {
            $table->unsignedInteger('order');
        });

        // $this->updateByAbbrev("td", 0); - Save for td
        $this->updateByAbbrev("ra", 1);
        $this->updateByAbbrev("ts", 2);
        $this->updateByAbbrev("ra2", 3);
        $this->updateByAbbrev("yr", 4);
        $this->updateByAbbrev("blitz", 5);
    }

    private function updateByAbbrev($abbrev, $index)
    {
        $ladder = Ladder::where("abbreviation", $abbrev)->first();
        $ladder->order = $index;
        $ladder->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ladders', function (Blueprint $table)
        {
            $table->dropColumn('order');
        });
    }
}
