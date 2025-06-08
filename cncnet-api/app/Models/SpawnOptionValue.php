<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpawnOptionValue extends Model {

	//

    public function name()
    {
        return $this->belongsTo(SpawnOptionString::class, 'name_id');
    }

    // Delete Me
    public function qmLadderRules()
    {
        return $this->belongsTo(QmLadderRules::class, 'qm_ladder_rules_id');
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function qmMap()
    {
        return $this->belongsTo(QmMap::class);
    }

    public function spawnOption()
    {
        return $this->belongsTo(SpawnOption::class);
    }

    public function value()
    {
        return $this->belongsTo(SpawnOptionString::class);
    }

}
