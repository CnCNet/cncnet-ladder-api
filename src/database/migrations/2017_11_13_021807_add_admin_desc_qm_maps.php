<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdminDescQmMaps extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qm_maps', function(Blueprint $table)
		{
			//
            $table->string("admin_description");
		});

        $qmMaps = \App\QmMap::all();
        foreach ($qmMaps as $qmMap)
        {
            $qmMap->admin_description = $qmMap->description;
            $qmMap->save();
        }
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qm_maps', function(Blueprint $table)
		{
			//
            $table->dropColumn("admin_description");
		});
	}

}
