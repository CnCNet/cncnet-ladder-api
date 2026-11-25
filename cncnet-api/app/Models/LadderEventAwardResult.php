<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LadderEventAwardResult extends Model
{
    use LogsActivity;

    const KIND_MANUAL = 'manual';
    const KIND_OVERRIDE = 'override';

    protected $fillable = ['ladder_event_id', 'award_key', 'kind', 'user_id', 'game_id', 'note', 'set_by'];

    // FR-032
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ladder_event_id', 'award_key', 'kind', 'user_id', 'game_id', 'note'])
            ->logOnlyDirty()
            ->useLogName('admin');
    }

    public function event()
    {
        return $this->belongsTo(LadderEvent::class, 'ladder_event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
