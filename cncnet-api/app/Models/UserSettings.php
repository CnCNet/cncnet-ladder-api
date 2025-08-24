<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class UserSettings extends Model
{
    use LogsActivity;

    protected static $logAttributes = [
        'user_id', 'disabledPointFilter', 'skip_score_screen', 'match_any_map', 'is_anonymous', 'match_ai', 'is_observer', 'allow_observers'
    ];
    protected static $logName = 'UserSettings';
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $recordEvents = ['updated'];
    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',
        'disabledPointFilter',
        'skip_score_screen',
        'match_any_map',
        'is_anonymous',
        'match_ai',
        'is_observer',
        'allow_observers',
    ];

    public function __construct()
    {
        $this->timestamps = false;
        $this->is_anonymous = false;     //by default users will not be anonymous
        $this->disabledPointFilter = false;    //by default point filter will be enabled
        $this->match_ai = true;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMatchAI()
    {
        return $this->match_ai;
    }

    public function getIsAnonymous()
    {
        return $this->is_anonymous == true;
    }

    public function getIsAnonymousForLadderHistory(LadderHistory $history): bool
    {
        // Only anonymous in current month.
        return $this->is_anonymous && $history->isCurrent();
    }
}
