<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapTierTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_tiers', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('map_pool_id');
            $table->unsignedInteger('tier');
            $table->integer('max_vetoes');  // max amount of vetoes allowed for this map_tier

            $table->foreign('map_pool_id')->references('id')->on('map_pools');
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
