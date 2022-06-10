<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateQmMatchesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qm_matches', function(Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->string('status');
            $table->integer('ladder_id');
            $table->integer('qm_map_id');
            $table->string('tunnel_ip');
            $table->integer('tunnel_port');
            $table->integer('seed');
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
        Schema::drop('qm_matches');
    }
}
