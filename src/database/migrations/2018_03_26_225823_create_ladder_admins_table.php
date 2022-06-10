<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLadderAdminsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ladder_admins', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('ladder_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->boolean('admin');
            $table->boolean('moderator');
            $table->boolean('tester');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ladder_admins');
	}

}
