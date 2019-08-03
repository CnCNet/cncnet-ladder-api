<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserIdentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_idents', function(Blueprint $table)
		{
            $table->string('user_id');
			$table->string('ident_id');
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
		Schema::table('user_idents', function(Blueprint $table)
		{
            $table->dropColumn('user_id');
            $table->dropColumn('ident_id');
		});
	}

}
