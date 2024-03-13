<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

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

        \App\Models\CountableObjectHeap::newFromNameDesc("UNB", "Units Bought");
        \App\Models\CountableObjectHeap::newFromNameDesc("INB", "Infantry Bought");
        \App\Models\CountableObjectHeap::newFromNameDesc("PLB", "Planes Bought");
        \App\Models\CountableObjectHeap::newFromNameDesc("VSB", "Ships Built");
        \App\Models\CountableObjectHeap::newFromNameDesc("BLB", "Buildings Bought");
        \App\Models\CountableObjectHeap::newFromNameDesc("UNK", "Units Killed");
        \App\Models\CountableObjectHeap::newFromNameDesc("INK", "Infantry Killed");
        \App\Models\CountableObjectHeap::newFromNameDesc("PLK", "Planes Killed");
        \App\Models\CountableObjectHeap::newFromNameDesc("VSK", "Ships Killed");
        \App\Models\CountableObjectHeap::newFromNameDesc("BLK", "Buildings Killed");
        \App\Models\CountableObjectHeap::newFromNameDesc("BLC", "Buildings Captured");
        \App\Models\CountableObjectHeap::newFromNameDesc("UNL", "Units Lost");
        \App\Models\CountableObjectHeap::newFromNameDesc("INL", "Infantry Lost");
        \App\Models\CountableObjectHeap::newFromNameDesc("PLL", "Planes Lost");
        \App\Models\CountableObjectHeap::newFromNameDesc("BLL", "Buildings Lost");
        \App\Models\CountableObjectHeap::newFromNameDesc("VSL", "Ships Lost");
        \App\Models\CountableObjectHeap::newFromNameDesc("CRA", "Crates Found");
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
