<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;

class MapPool extends Model
{
    use LogsActivity, HasFactory;

    protected static $logAttributes = [
        'name', 'ladder_id', 'invalid_faction_pairs', 'forced_faction_ratio', 'forced_faction_id'
    ];
    protected static $logName = 'MapPool';
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    protected $casts = [
        'invalid_faction_pairs' => 'array',
        'forced_faction_ratio'  => 'float',
        'forced_faction_id'     => 'integer',
    ];

    protected $fillable = [
        'name',
        'ladder_id'
    ];

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    // Delete Me
    public function qmLadderRules()
    {
        return $this->belongsTo(QmLadderRules::class, 'qm_ladder_rules_id');
    }

    public function maps()
    {
        return $this->hasMany(QmMap::class)->valid()->orderBy('bit_idx');
    }

    public function tiers()
    {
        return $this->hasMany(MapTier::class, 'map_pool_id');
    }

    public function invalidPairs(): array
    {
        return $this->invalid_faction_pairs ?? [];
    }

    public function isValidPair(int $faction1, int $faction2): bool
    {
        $pairs = $this->invalidPairs();
        if (!is_array($pairs) || empty($pairs))
        {
            return true; // No limitations.
        }

        $factionA = min($faction1, $faction2);
        $factionB = max($faction1, $faction2);

        foreach ($pairs as $p)
        {
            if (!is_array($p) || count($p) !== 2)
            {
                Log::warning('isValidPair: map pool contains invalid forbidden faction pairs (' . $p . ')');
                continue;
            }

            $pairFaction1 = (int)min($p[0], $p[1]);
            $pairFaction2 = (int)max($p[0], $p[1]);

            if ($pairFaction1 === $factionA && $pairFaction2 === $factionB)
            {
                return false;
            }
        }

        return true;
    }
}
