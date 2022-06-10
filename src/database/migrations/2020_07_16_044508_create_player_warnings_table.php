<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerWarningsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('player_alerts', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('player_id');
            $table->text('message');
            $table->timeStamp('expires_at')->nullable();
            $table->timeStamp('seen_at')->nullable();;
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('player_alerts');
    }
}
