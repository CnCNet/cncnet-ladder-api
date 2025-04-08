<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpawnOptionType extends Model {

	//
    const SPAWN_INI = 1;
    const SPAWNMAP_INI = 2;
    const COPY_FILE = 3;
    const PREPEND_FILE = 4;
    const APPEND_FILE = 5;
    const MERGE_FILE = 6;

    // TODO missing types 'MERGE_FILE' and 'APPEND_FILE'

    public static function findOrCreate($name)
    {
        $t = SpawnOptionType::where('name', '=', $name)->first();
        if ($t === null)
        {
            $t = new SpawnOptionType($name);
            $t->save();
        }

        return $t;
    }

    public function __construct($name = "")
    {
        $this->name = $name;
    }

    public function spawnOptions()
    {
        return $this->hasMany(SpawnOption::class, 'type_id');
    }
}
