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
            $table->enum('game', ["ra", "ts", "yr", "td", "d2k", "ss"]);
            $table->boolean("is_offline")->default(false);
        });

        $this->addLadder("ss", "ss", "Sole Survivor");
        $this->addLadder("td", "td", "Tiberian Dawn");
        $this->addLadder("d2k", "d2k", "Dune 2000");
        $this->addLadder("mo", "yr", "Mental Omega");

        $this->updateByAbbrev("ss", 0, "ss", true);
        $this->updateByAbbrev("d2k", 1, "d2k", true);
        $this->updateByAbbrev("td", 2, "td", true);
        $this->updateByAbbrev("ra", 3, "ra");
        $this->updateByAbbrev("ts", 4, "ts");
        $this->updateByAbbrev("ra2", 5, "yr");
        $this->updateByAbbrev("yr", 6, "yr");
        $this->updateByAbbrev("blitz", 7, "yr");
        $this->updateByAbbrev("mo", 8, "yr", true);

        $raLadder = Ladder::where("abbreviation", "ra")->first();
        $yrLadder = Ladder::where("abbreviation", "yr")->first();

        $tdLadder = Ladder::where("abbreviation", "td")->first();
        $this->addLadderHistory($tdLadder->id);
        $this->addLadderSides($raLadder, $tdLadder);

        $ssLadder = Ladder::where("abbreviation", "ss")->first();
        $this->addLadderHistory($ssLadder->id);
        $this->addLadderSides($raLadder, $ssLadder);

        $d2kLadder = Ladder::where("abbreviation", "d2k")->first();
        $this->addLadderHistory($d2kLadder->id);
        $this->addLadderSides($raLadder, $d2kLadder);

        $moLadder = Ladder::where("abbreviation", "mo")->first();
        $this->addLadderHistory($moLadder->id);
        $this->addLadderSides($yrLadder, $moLadder);
    }

    private function addLadderSides($ladderToCopyFrom, $ladderToApplyTo)
    {
        # Add sides
        $sides = \App\Side::where('ladder_id', $ladderToCopyFrom->id)->get();
        for ($i = 0; $i < count($sides); ++$i)
        {
            $side = new \App\Side();
            $side->ladder_id = $ladderToApplyTo->id;
            $side->local_id = $i;
            $side->name = $sides[$i]->name;
            $side->save();
        }

        # Create ladder rules			
        $yrLadderRules = \App\QmLadderRules::where('ladder_id', $ladderToCopyFrom->id)->first();
        $newLadderRules = $yrLadderRules->replicate()->fill([
            'ladder_id' => $ladderToApplyTo->id
        ]);
        $newLadderRules->save();

        #Copy over the YR spawn options
        $options = \App\SpawnOptionValue::where('ladder_id', $ladderToCopyFrom->id)->get();

        foreach ($options as $option)
        {
            $o = new \App\SpawnOptionValue;
            $o->ladder_id = $ladderToApplyTo->id;
            $o->spawn_option_id = $option->spawn_option_id;
            $o->value_id = $option->value_id;
            $o->save();
        }
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

    private function updateByAbbrev($abbrev, $order, $gameType, $isOffline = false)
    {
        $ladder = Ladder::where("abbreviation", $abbrev)->first();
        $ladder->order = $order;
        $ladder->game = $gameType;
        $ladder->is_offline = $isOffline;
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
