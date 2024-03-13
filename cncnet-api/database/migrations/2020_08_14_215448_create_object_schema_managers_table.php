<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateObjectSchemaManagersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('object_schema_managers', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('game_object_schema_id');
            $table->integer('user_id');
            $table->timestamps();
        });

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
        Schema::drop('object_schema_managers');
    }
}
