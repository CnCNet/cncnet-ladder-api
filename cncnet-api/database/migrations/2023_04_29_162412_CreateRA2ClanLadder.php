<?php

use Illuminate\Database\Migrations\Migration;

class CreateRA2ClanLadder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $ra2Ladder = \App\Models\Ladder::where('abbreviation', 'ra2')->first();
 
        $ra2Cl = \App\Models\Ladder::where('abbreviation', 'ra2-cl')->first();
 
        #add sides
        $sides = \App\Models\Side::where('ladder_id', $ra2Ladder->id)->get();
        for ($i = 0; $i < count($sides); ++$i)
        {
            $side = new \App\Models\Side();
            $side->ladder_id = $ra2Cl->id;
            $side->local_id = $i;
            $side->name = $sides[$i]->name;
            $side->save();
        }
 
        #create ladder rules			
        $ra2LadderRules = \App\Models\QmLadderRules::where('ladder_id', $ra2Ladder->id)->first();
        $newLadderRules = $ra2LadderRules->replicate()->fill([
            'ladder_id' => $ra2Cl->id
        ]);
        $newLadderRules->save();
 
        #Copy over the RA2 spawn options
        $options = \App\Models\SpawnOptionValue::where('ladder_id', $ra2Ladder->id)->get();
 
        foreach ($options as $option)
        {
            $o = new \App\Models\SpawnOptionValue;
            $o->ladder_id = $ra2Cl->id;
            $o->spawn_option_id = $option->spawn_option_id;
            $o->value_id = $option->value_id;
            $o->save();
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
