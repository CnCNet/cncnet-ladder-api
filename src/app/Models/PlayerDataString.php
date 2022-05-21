<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerDataString extends Model
{

    public $timestamps = false;
    //
    public static function findValue($value)
    {
        if ($value === null)
            return null;

        $v = PlayerDataString::where('value', '=', $value)->first();
        if ($v === null)
        {
            $v = new PlayerDataString;
            $v->value = $value;
            $v->save();
        }
        return $v;
    }
}
