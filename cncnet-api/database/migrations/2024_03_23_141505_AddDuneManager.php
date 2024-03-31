<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use App\Models\GameObjectSchema;
use App\Models\ObjectSchemaManager;

class AddDuneManager extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (User::where('group', '=', User::God)->get() as $user)
        {
            foreach (GameObjectSchema::all() as $gos)
            {
                ObjectSchemaManager::firstOrCreate(['game_object_schema_id' => $gos->id, 'user_id' => $user->id]);
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
