<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

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

        $qmMaps = \App\Models\QmMap::all();
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
