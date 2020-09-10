<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGameObjectSchemasTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_object_schemas', function(Blueprint $table)
        {
            $table->increments('id');
            $table->text('name');
            $table->timestamps();
        });

        Schema::table('countable_game_objects', function(Blueprint $table)
        {
            $table->integer('game_object_schema_id');
        });

        Schema::table('ladders', function(Blueprint $table)
        {
            $table->integer('game_object_schema_id');
        });

        foreach (\App\Ladder::all() as $ladder)
        {
            $gos = \App\GameObjectSchema::firstOrCreate([ 'name' => "{$ladder->name} Schema" ]);
            $ladder->game_object_schema_id = $gos->id;
            $ladder->save();

            foreach (\App\CountableGameObject::where('ladder_id', '=', $ladder->id)->get() as $cgo)
            {
                $cgo->game_object_schema_id = $gos->id;
                $cgo->save();
            }
        }

        Schema::table('countable_game_objects', function(Blueprint $table)
        {
            $table->dropColumn('ladder_id');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('countable_game_objects', function(Blueprint $table)
        {
            $table->integer('ladder_id');
        });

        foreach (\App\Ladder::all() as $ladder)
        {
            foreach (\App\CountableGameObject::where('game_object_schema_id', '=', $ladder->game_object_schema_id)->get() as $cgo)
            {
                $cgo->ladder_id = $ladder->id;
                $cgo->save();
            }
        }

        Schema::table('ladders', function(Blueprint $table)
        {
            $table->dropColumn('game_object_schema_id');
        });

        Schema::table('countable_game_objects', function(Blueprint $table)
        {
            $table->dropColumn('game_object_schema_id');
        });

        Schema::drop('game_object_schemas');
    }
}
