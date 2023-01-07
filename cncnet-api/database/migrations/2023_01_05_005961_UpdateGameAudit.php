<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateGameAudit extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_audit', function (Blueprint $table)
        {
            $table->dropForeign(['user_id']);

            if (Schema::hasColumn('game_audit', 'user_id'))
            {
                $table->dropColumn('user_id');
            }

            if (!Schema::hasColumn('game_audit', 'username'))
            {
                $table->string('username')->nullable(false);
            }
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
