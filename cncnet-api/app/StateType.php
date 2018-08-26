<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class StateType extends Model {

	//
    public $timestamps = false;

    public static function findByName($name)
    {
        $state = StateType::where('name','=', $name)->first();

        if ($state === null)
        {
            $state = new StateType;
            $state->name = $name;
            $state->save();
        }
        return $state;
    }

    public function qmMatchStates()
    {
        return $this->hasMany('App\QmMatchState');
    }
}
