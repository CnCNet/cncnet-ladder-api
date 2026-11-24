<?php

namespace App\Models;

use App\Services\LadderEvents\LadderEventDefinition;
use Illuminate\Database\Eloquent\Model;

class LadderEvent extends Model
{
    const STATUS_UPCOMING = 'upcoming';
    const STATUS_LIVE = 'live';
    const STATUS_PENDING_FINAL = 'pending_final';
    const STATUS_FINAL = 'final';

    protected $fillable = [
        'slug',
        'ladder_id',
        'status_override',
        'computed_payload',
        'computed_at',
        'frozen_payload',
        'finalised_at',
        'finalised_by',
    ];

    protected $casts = [
        'computed_payload' => 'array',
        'frozen_payload' => 'array',
        'computed_at' => 'datetime',
        'finalised_at' => 'datetime',
    ];

    private ?LadderEventDefinition $definitionCache = null;

    // FR-032: finalise / un-finalise are logged explicitly by LadderEventService with the acting user.
    // Payload rewrites from the scheduler are not logged.

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function groupMembers()
    {
        return $this->hasMany(LadderEventGroupMember::class);
    }

    public function awardResults()
    {
        return $this->hasMany(LadderEventAwardResult::class);
    }

    /**
     * Resolve an event by slug. The slug must exist both in config and in the table (FR-029).
     */
    public static function findBySlug(string $slug): ?LadderEvent
    {
        if (!is_array(config('ladder_events.' . $slug)))
        {
            return null;
        }

        return static::where('slug', '=', $slug)->first();
    }

    public function definition(): LadderEventDefinition
    {
        if ($this->definitionCache === null || $this->definitionCache->slug !== $this->slug)
        {
            $this->definitionCache = LadderEventDefinition::fromConfig($this->slug, config('ladder_events.' . $this->slug) ?? []);
        }

        return $this->definitionCache;
    }

    /**
     * Ladder history rows for the configured `short`s, in config order.
     * @return \Illuminate\Support\Collection<int, LadderHistory>
     */
    public function histories()
    {
        $shorts = $this->definition()->histories;

        $rows = LadderHistory::where('ladder_id', '=', $this->ladder_id)
            ->whereIn('short', $shorts)
            ->get()
            ->keyBy('short');

        $missing = array_diff($shorts, $rows->keys()->all());
        if (count($missing) > 0)
        {
            throw new \RuntimeException("Ladder event {$this->slug}: ladder history not found for " . implode(', ', $missing));
        }

        return collect($shorts)->map(fn ($short) => $rows[$short])->values();
    }
}
