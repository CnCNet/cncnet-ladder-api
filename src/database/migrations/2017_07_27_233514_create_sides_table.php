<?php

use App\Models\Ladder;
use App\Models\Side;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateSidesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('sides', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('ladder_id');
            $table->integer('local_id');
            $table->string('name');
			$table->timestamps();
		});
        $yr_sides = ["America","Korea","France","Germany","Great Britain","Libya","Iraq","Cuba","Russia","Yuri"];
        for ($i = 0; $i < count($yr_sides); ++$i)
        {

            // Yuri and France are only allowed through random

            if ($yr_sides[$i] != "France" && $yr_sides[$i] != "Yuri")
            {
                $side = new Side();
                $side->ladder_id = Ladder::where('abbreviation', 'yr')->first()->id;
                $side->local_id = $i;
                $side->name = $yr_sides[$i];
                $side->save();
            }
        }
        $random = new Side();
        $random->ladder_id = Ladder::where('abbreviation', 'yr')->first()->id;
        $random->local_id = -1;
        $random->name = "Random";
        $random->save();

        $ts_side0 = new Side();
        $ts_side0->ladder_id = Ladder::where('abbreviation', 'ts')->first()->id;
        $ts_side0->local_id = 0;
        $ts_side0->name = 'GDI';
        $ts_side0->save();

        $ts_side0 = new Side();
        $ts_side0->ladder_id = Ladder::where('abbreviation', 'ts')->first()->id;
        $ts_side0->local_id = 1;
        $ts_side0->name = 'NOD';
        $ts_side0->save();

        $random = new Side();
        $random->ladder_id = Ladder::where('abbreviation', 'ts')->first()->id;
        $random->local_id = -1;
        $random->name = "Random";
        $random->save();
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('sides');
	}

}
