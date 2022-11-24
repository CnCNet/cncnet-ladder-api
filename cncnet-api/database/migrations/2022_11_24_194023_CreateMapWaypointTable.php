<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapWaypointTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_waypoints', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('bit_idx')->unsigned();
            $table->integer('x')->unsigned();
            $table->integer('y')->unsigned();
            $table->integer('map_header_id')->unsigned();
            $table->foreign('map_header_id')->references('id')->on('map_headers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('map_waypoints');
    }
}
