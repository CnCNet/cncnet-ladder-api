<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClanInvitationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clan_invitations', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('clan_id');
            $table->integer('author_id');
            $table->integer('player_id');
            $table->enum('type', ['invited', 'cancelled', 'joined', 'kicked', 'left', 'promoted', 'demoted']);
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
        Schema::drop('clan_invitations');
    }
}
