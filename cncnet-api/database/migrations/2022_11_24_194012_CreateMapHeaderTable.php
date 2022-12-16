<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapHeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_headers', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('width');
            $table->integer('height');
            $table->integer('startX');
            $table->integer('startY');
            $table->integer('numStartingPoints');
            $table->integer('map_id');
            $table->foreign('map_id')->references('id')->on('maps')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('map_headers');
    }
}
