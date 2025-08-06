<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_ratings', function (Blueprint $table) {
            $table->dropColumn('peak_rating');
        });

        Schema::table('user_ratings', function (Blueprint $table) {
            $table->unsignedInteger('ladder_id')->after('user_id');
            $table->integer('deviation')->after('rating');
            $table->integer('elo_rank')->after('deviation');
            $table->integer('alltime_rank')->after('elo_rank');
            $table->boolean('active')->default(1)->after('rated_games');
        });
    }

    public function down()
    {
        Schema::table('user_ratings', function (Blueprint $table) {
            $table->integer('peak_rating');
            $table->dropColumn(['ladder_id', 'deviation', 'elo_rank', 'alltime_rank', 'active']);
        });
    }
};
