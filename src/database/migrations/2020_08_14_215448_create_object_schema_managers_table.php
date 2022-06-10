<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
        Schema::drop('object_schema_managers');
    }
}
