<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use \Spatie\Activitylog\LogOptions;

class SpawnOptionValue extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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
