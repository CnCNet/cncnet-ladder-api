<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('player_game_reports', function (Blueprint $table) {
            $table->string('team')->nullable(); // used for 2v2 ladder
        });
    }

    public function down()
    {
        Schema::table('player_game_reports', function (Blueprint $table) {
            $table->dropColumn('team');
        });
    }
};
