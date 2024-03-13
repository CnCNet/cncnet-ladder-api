<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCountableGameObjectsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('countable_game_objects', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('ladder_id')->unsigned();
            $table->char("heap_name", 3);
            $table->integer('heap_id')->index();
            $table->string('name');
            $table->string('cameo');
            $table->integer('cost');
            $table->integer('value');
            $table->string('ui_name');
            $table->timestamps();
            $table->index(['heap_name', 'name']);
		});

        foreach (\App\Models\Ladder::all() as $ladder)
        {
            $heaps = array ("CRA","BLC","BLK","PLK","UNK","INK","BLL","PLL","UNL","INL","BLB","PLB","UNB","INB","VSK","VSL","VSB");

            $objects = config('types.'.strtoupper($ladder->abbreviation));
            $cameos = config('cameos.'.strtoupper($ladder->abbreviation));

            foreach ($heaps as $heapDesc)
            {
                $heapName = substr($heapDesc, 0, 2);
                if (!array_key_exists($heapName, $objects)) continue;

                foreach ($objects[$heapName] as $id => $name)
                {
                    $countable = new \App\Models\CountableGameObject;
                    $countable->ladder_id = $ladder->id;
                    $countable->heap_name = $heapDesc;
                    $countable->heap_id = $id;
                    $countable->name = $name;
                    $countable->cost = 0;
                    $countable->value = 0;
                    $countable->ui_name = $name;

                    if (array_key_exists($name, $cameos))
                        $countable->cameo = $cameos[$name];

                    $countable->save();
                }
            }
        }
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('countable_game_objects');
	}

}
