<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIrcAssociationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('irc_associations', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('irc_hostmask_id');
            $table->integer('user_id');
            $table->integer('ladder_id');
            $table->integer('player_id');
            $table->integer('clan_id');
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
        Schema::drop('irc_associations');
    }
}
