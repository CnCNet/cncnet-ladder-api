<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use App\SpawnOptionString;
use App\SpawnOptionType;

class SpawnOption extends Model {

	//
    public static function makeOne($type_id, $name_str, $string1, $string2)
    {
        $inst = new self();
        $inst->type_id = $type_id;
        $inst->name_id = SpawnOptionString::findOrCreate($name_str)->id;
        $inst->string1_id = SpawnOptionString::findOrCreate($string1)->id;
        $inst->string2_id = SpawnOptionString::findOrCreate($string2)->id;
        return $inst;
    }

    public function type()
    {
        return $this->belongsTo('App\SpawnOptionType', 'type_id');
    }

    public function name()
    {
        return $this->belongsTo('App\SpawnOptionString', 'name_id');
    }

    public function string1()
    {
        return $this->belongsTo('App\SpawnOptionString', 'string1_id');
    }

    public function string2()
    {
        return $this->belongsTo('App\SpawnOptionString', 'string2_id');
    }

    public function spawnOptionValues()
    {
        return $this->hasMany('\App\SpawnOption');
    }
}
