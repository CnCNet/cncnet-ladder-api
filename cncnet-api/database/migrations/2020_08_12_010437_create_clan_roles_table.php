<?php

use App\Models\ClanRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClanRolesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clan_roles', function(Blueprint $table)
        {
            $table->increments('id');
            $table->text('value');
            $table->timestamps();
        });
        ClanRole::firstOrCreate(['value' => "Owner"]);
        ClanRole::firstOrCreate(['value' => "Manager"]);
        ClanRole::firstOrCreate(['value' => "Member"]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('clan_roles');
    }
}
