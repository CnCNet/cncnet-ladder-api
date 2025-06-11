<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameObjectSchema extends Model {

    use HasFactory;

    //
    protected $fillable = [ 'name' ];

    public static function managedBy($user)
    {
        $result = array();
        foreach (ObjectSchemaManager::where('user_id', '=', $user->id)->get() as $manager)
        {
            $result[] = $manager->gameObjectSchema;
        }
        return $result;
    }

    public function managers()
    {
        return $this->hasMany(ObjectSchemaManager::class);
    }

    public function countableGameObjects()
    {
        return $this->hasMany(CountableGameObject::class);
    }
}
