<?php

use App\Ladder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTdD2kLadders extends Migration
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
            $table->dropColumn("game");
        });

        Schema::table('ladders', function (Blueprint $table)
        {
            $table->enum('game', ["ra", "ts", "yr", "td", "d2k"]);
        });

        $this->addLadder("td", "td", "Tiberian Dawn");
        $this->addLadder("d2k", "d2k", "Dune 2000");
        $this->addLadder("mo", "yr", "Mental Omega");

        $this->updateByAbbrev("d2k", 0, "d2k");
        $this->updateByAbbrev("td", 1, "td");
        $this->updateByAbbrev("ra", 2, "ra");
        $this->updateByAbbrev("ts", 3, "ts");
        $this->updateByAbbrev("ra2", 4, "yr");
        $this->updateByAbbrev("yr", 5, "yr");
        $this->updateByAbbrev("blitz", 6, "yr");
        $this->updateByAbbrev("mo", 7, "yr");


        $ladder = Ladder::where("abbreviation", "td")->first();
        $this->addLadderHistory($ladder->id);

        $ladder = Ladder::where("abbreviation", "d2k")->first();
        $this->addLadderHistory($ladder->id);

        $ladder = Ladder::where("abbreviation", "mo")->first();
        $this->addLadderHistory($ladder->id);
    }

    private function addLadderHistory($ladderId)
    {
        $lc = new \App\Http\Controllers\LadderController;
        $lc->addLadder($ladderId); #create ladder histories
    }

    private function addLadder($abbrev, $game, $name)
    {
        $ladder = new Ladder();
        $ladder->name = $name;
        $ladder->game = $game;
        $ladder->abbreviation = $abbrev;
        $ladder->save();
    }

    private function updateByAbbrev($abbrev, $index, $gameType)
    {
        $ladder = Ladder::where("abbreviation", $abbrev)->first();
        $ladder->order = $index;
        $ladder->game = $gameType;
        $ladder->save();
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
