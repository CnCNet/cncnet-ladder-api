<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameObjectSchema extends Model
{

    //
    protected $fillable = ['name'];

    public static function managedBy($user)
    {
        $result = array();
        foreach (\App\Models\ObjectSchemaManager::where('user_id', '=', $user->id)->get() as $manager)
        {
            $result[] = $manager->gameObjectSchema;
        }
        return $result;
    }

    public function managers()
    {
        return $this->hasMany('App\Models\ObjectSchemaManager');
    }

    public function countableGameObjects()
    {
        return $this->hasMany('App\Models\CountableGameObject');
    }
}
