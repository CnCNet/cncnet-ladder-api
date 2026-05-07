<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserSettings extends Model
{
    use LogsActivity;

    // Observer mode constants
    const OBSERVER_MODE_PLAY = 'play';
    const OBSERVER_MODE_OBSERVE_ONLY = 'observe_only';
    const OBSERVER_MODE_PLAY_AND_OBSERVE = 'play_and_observe';

    protected static $recordEvents = ['updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['allow_2v2_ladders', 'is_anonymous', 'other_setting1', 'other_setting2', 'observer_mode', 'disabledPointFilter'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'user_id',
        'disabledPointFilter',
        'skip_score_screen',
        'match_any_map',
        'is_anonymous',
        'match_ai',
        'observer_mode',
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

    /**
     * Check if user wants to observe only (never play)
     */
    public function wantsToObserveOnly(): bool
    {
        return $this->observer_mode === self::OBSERVER_MODE_OBSERVE_ONLY;
    }

    /**
     * Check if user wants to play and observe (play first, observe if excluded)
     */
    public function canPlayAndObserve(): bool
    {
        return $this->observer_mode === self::OBSERVER_MODE_PLAY_AND_OBSERVE;
    }

    /**
     * Check if user has any observer mode enabled
     */
    public function hasObserverModeEnabled(): bool
    {
        return in_array($this->observer_mode, [
            self::OBSERVER_MODE_OBSERVE_ONLY,
            self::OBSERVER_MODE_PLAY_AND_OBSERVE
        ]);
    }
}
