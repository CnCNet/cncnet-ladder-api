<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCountableObjectHeapsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('countable_object_heaps', function(Blueprint $table)
		{
			$table->increments('id');
            $table->string("name");
            $table->string("description");
			$table->timestamps();
		});

        \App\CountableObjectHeap::newFromNameDesc("UNB", "Units Bought");
        \App\CountableObjectHeap::newFromNameDesc("INB", "Infantry Bought");
        \App\CountableObjectHeap::newFromNameDesc("PLB", "Planes Bought");
        \App\CountableObjectHeap::newFromNameDesc("VSB", "Ships Built");
        \App\CountableObjectHeap::newFromNameDesc("BLB", "Buildings Bought");
        \App\CountableObjectHeap::newFromNameDesc("UNK", "Units Killed");
        \App\CountableObjectHeap::newFromNameDesc("INK", "Infantry Killed");
        \App\CountableObjectHeap::newFromNameDesc("PLK", "Planes Killed");
        \App\CountableObjectHeap::newFromNameDesc("VSK", "Ships Killed");
        \App\CountableObjectHeap::newFromNameDesc("BLK", "Buildings Killed");
        \App\CountableObjectHeap::newFromNameDesc("BLC", "Buildings Captured");
        \App\CountableObjectHeap::newFromNameDesc("UNL", "Units Lost");
        \App\CountableObjectHeap::newFromNameDesc("INL", "Infantry Lost");
        \App\CountableObjectHeap::newFromNameDesc("PLL", "Planes Lost");
        \App\CountableObjectHeap::newFromNameDesc("BLL", "Buildings Lost");
        \App\CountableObjectHeap::newFromNameDesc("VSL", "Ships Lost");
        \App\CountableObjectHeap::newFromNameDesc("CRA", "Crates Found");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('countable_object_heaps');
	}

}
