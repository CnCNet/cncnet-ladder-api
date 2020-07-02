<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class SpawnOptionString extends Model {

    protected $table = 'spawn_option_strings';

	//
    public static function findOrCreate($string)
    {
        $t = SpawnOptionString::where('string', '=', $string)->first();

        if ($t === null)
        {
            $t = new SpawnOptionString();
            $t->string = $string;
            $t->save();
        }

        return $t;
    }
}
