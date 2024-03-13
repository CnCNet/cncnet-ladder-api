<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObjectSchemaManager extends Model {

	//
    protected $fillable = [ 'game_object_schema_id', 'user_id' ];

    public function gameObjectSchema()
    {
        return $this->belongsTo(GameObjectSchema::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
