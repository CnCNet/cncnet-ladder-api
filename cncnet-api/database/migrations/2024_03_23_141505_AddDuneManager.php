<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDuneManager extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (\App\User::where('group', '=', \App\User::God)->get() as $user)
        {
            foreach (\App\GameObjectSchema::all() as $gos)
            {
                \App\ObjectSchemaManager::firstOrCreate(['game_object_schema_id' => $gos->id, 'user_id' => $user->id ]);
            }
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
