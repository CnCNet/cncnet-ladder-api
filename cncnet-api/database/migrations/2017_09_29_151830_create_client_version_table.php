<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientVersionTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('client_version', function(Blueprint $table)
		{
            $table->string("version");
            $table->string("link");
            $table->string("format");
            $table->string("name");
            $table->string("platform");
		});
        DB::table('client_version')->insert(["version"  => "1.10",
                                             "link"     => "http://downloads.cncnet.org/CnCNetQM.exe",
                                             "format"   => "raw",
                                             "name"     => "CnCNetQM.exe",
                                             "platform" => "win32"]
        );
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('client_version');
	}

}
