<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToClanTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clan_caches', function (Blueprint $table)
        {
            $table->integer('country')->nullable();
            $table->index(['ladder_history_id', 'clan_id']);
            $table->index(['ladder_history_id', 'points']);
        });

        Schema::table('player_game_reports', function (Blueprint $table)
        {
            $table->index('clan_id');
        });

        Schema::table('game_reports', function (Blueprint $table)
        {
            $table->index('clan_id');
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
