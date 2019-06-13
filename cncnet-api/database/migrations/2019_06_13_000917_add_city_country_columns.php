<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use MaxMind\Db\Reader;

class AddCityCountryColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('ip_addresses', function(Blueprint $table)
		{
			//
            $table->string('city');
            $table->string('country');
		});

        $reader = new Reader(config('database.mmdb.file'));

        foreach (\App\IpAddress::all() as $ip)
        {
            $mmData = $reader->get($ip->address);
            try
            {
                if (array_key_exists("country", $mmData))
                    $ip->country = $mmData["country"]["iso_code"];

                if (array_key_exists("city", $mmData))
                    $ip->city = $mmData["city"]["names"]["en"];
            }
            catch (Exception $e)
            {
                error_log($e->getMessage());
            }
            $ip->save();
        }
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('ip_addresses', function(Blueprint $table)
		{
			//
            $table->dropColumn('city');
            $table->dropColumn('country');
		});
	}

}
