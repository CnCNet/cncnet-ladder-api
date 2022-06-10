<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRefreshedToIrcAssociations extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('irc_associations', function(Blueprint $table)
        {
            //
            $table->timestamp('refreshed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('irc_associations', function(Blueprint $table)
        {
            //
            $table->dropColumn('refreshed_at');
        });
    }
}
