<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class CountableObjectHeap extends Model {

	//
    public static function newFromNameDesc($name, $desc)
    {
        $obj = new CountableObjectHeap;
        $obj->name = $name;
        $obj->description = $desc;
        $obj->save();
    }
}
