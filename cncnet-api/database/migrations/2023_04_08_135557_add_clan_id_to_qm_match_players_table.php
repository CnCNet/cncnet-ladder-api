<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClanIdToQmMatchPlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qm_match_players', function (Blueprint $table)
        {
            $table->unsignedInteger('clan_id')->nullable()->default(null);
            $table->foreign('clan_id')->references('id')->on('clans');
        });
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
