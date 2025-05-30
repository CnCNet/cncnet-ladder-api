<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapSideString extends Model {

    public $timestamps = false;
	//
    public static function findValue($value)
    {
        if ($value === null)
            return null;

        $v = MapSideString::where('value', '=', $value)->first();
        if ($v === null)
        {
            $v = new MapSideString;
            $v->value = $value;
            $v->save();
        }
        return $v;
    }
}
