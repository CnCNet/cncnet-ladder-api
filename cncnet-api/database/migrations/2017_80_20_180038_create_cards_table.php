<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCardsTable extends Migration 
{
	public function up()
	{
		Schema::create('cards', function(Blueprint $table)
		{
			$table->increments('id');
            $table->string('name');
            $table->string('short');
		});

        $this->seed("General Carville", "carville");
        $this->seed("Yuri Commander", "yuri");
        $this->seed("Yuri Commander Closeup", "yuri-closeup");
        $this->seed("Kane", "kane");
        $this->seed("GDI", "gdi");
        $this->seed("Nod", "nod");
	}

    private function seed($name, $short)
    {
        $card = new \App\Models\Card();
        $card->short = $short;
        $card->name = $name;
        $card->save();
    }

    public function down()
    {
    	Schema::drop('cards');
    }
}