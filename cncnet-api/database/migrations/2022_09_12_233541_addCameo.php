<?php

use Illuminate\Database\Migrations\Migration;

class AddCameo extends Migration
{

	/**
	 * Add cameos for demolition truck, grand cannon, and chaos drone.
	 *
	 * @return void
	 */
	public function up()
	{
		$gcos = \App\Models\CountableGameObject::where('name', 'DTRUCK')->get();
		foreach ($gcos as $gco)
		{
			$gco->cameo = 'dtruckicon';
			$gco->save();
		}

		$gcos = \App\Models\CountableGameObject::where('name', 'GTGCAN')->get();
		foreach ($gcos as $gco)
		{
			$gco->cameo = 'gcanicon';
			$gco->save();
		}

		$gcos = \App\Models\CountableGameObject::where('name', 'CAOS')->get();
		foreach ($gcos as $gco)
		{
			$gco->cameo = 'caosicon';
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
