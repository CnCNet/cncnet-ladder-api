<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpawnOption extends Model {

	/**
     * type_id: the spawn option type, see SpawnOptionType.php
     * name_str: the name of this option, you can pass anything - better to pass a name so you know what the option does
     * string1: this is the spawn option key, e.g. [Basic]
     * strin2: this is the spawn option attribute, e.g. Author
     */
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
        return $this->belongsTo(SpawnOptionType::class, 'type_id');
    }

    public function name()
    {
        return $this->belongsTo(SpawnOptionString::class, 'name_id');
    }

    public function string1()
    {
        return $this->belongsTo(SpawnOptionString::class, 'string1_id');
    }

    public function string2()
    {
        return $this->belongsTo(SpawnOptionString::class, 'string2_id');
    }

    public function spawnOptionValues()
    {
        return $this->hasMany(SpawnOption::class);
    }
}
