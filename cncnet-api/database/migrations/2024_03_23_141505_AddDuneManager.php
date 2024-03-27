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
        foreach (\App\Models\User::where('group', '=', \App\Models\User::God)->get() as $user)
        {
            foreach (\App\Models\GameObjectSchema::all() as $gos)
            {
                \App\Models\ObjectSchemaManager::firstOrCreate(['game_object_schema_id' => $gos->id, 'user_id' => $user->id ]);
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
