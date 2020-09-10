<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class ObjectSchemaManager extends Model {

	//
    protected $fillable = [ 'game_object_schema_id', 'user_id' ];

    public function gameObjectSchema()
    {
        return $this->belongsTo('App\GameObjectSchema');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
