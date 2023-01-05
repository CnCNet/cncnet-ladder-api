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
        $records = \App\GameAudit::all();

        Schema::table('game_audit', function (Blueprint $table)
		{
            $table->dropForeign('user_id');
            $table->dropColumn('user_id');
            $table->string('username')->nullable(false);
        });

        foreach ($records as $record)
        {
            $record->username = $record->user->name;
            $record->save();
        }

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
