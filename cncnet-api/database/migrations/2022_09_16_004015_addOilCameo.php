<?php

use Illuminate\Database\Schema\Blueprint;
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
		$gcos = \App\CountableGameObject::where('name', 'CAOILD')->get();
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
