<?php

use Illuminate\Database\Migrations\Migration;

class AddOilCameo extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$gcos = \App\Models\CountableGameObject::where('name', 'CAOILD')->get();
		foreach ($gcos as $gco)
		{
			$gco->cameo = 'psybicon';
			$gco->save();
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
