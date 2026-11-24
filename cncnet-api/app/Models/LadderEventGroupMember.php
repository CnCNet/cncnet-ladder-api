<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LadderEventGroupMember extends Model
{
    use LogsActivity;

    protected $fillable = ['ladder_event_id', 'group_key', 'user_id'];

    // FR-032
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ladder_event_id', 'group_key', 'user_id'])
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
