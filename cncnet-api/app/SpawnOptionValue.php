<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class SpawnOptionValue extends Model {

	//

    public function name()
    {
        return $this->belongsTo('App\SpawnOptionString', 'name_id');
    }

    // Delete Me
    public function qmLadderRules()
    {
        return $this->belongsTo('\App\QmLadderRules', 'qm_ladder_rules_id');
    }

    public function ladder()
    {
        return $this->belongsTo('\App\Ladder');
    }

    public function qmMap()
    {
        return $this->belongsTo('\App\QmMap');
    }

    public function spawnOption()
    {
        return $this->belongsTo('\App\SpawnOption');
    }

    public function value()
    {
        return $this->belongsTo('\App\SpawnOptionString');
    }

}
