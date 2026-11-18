<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;

class QmMap extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ladder_id',
                'map_pool_id',
                'map_id',
                'valid',
                'description',
                'allowed_sides',
                'team1_spawn_order',
                'team2_spawn_order',
                'map_tier',
                'random_spawns',
                'default_reject',
                'weight',
                'admin_description',
                'rejectable'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();

        // Defensive fallback: Auto-set ladder_id from mapPool if missing
        // Primary fix is in MapPoolController, but this catches edge cases
        // (manual creation via tinker, imports, etc)
        static::saving(function ($qmMap) {
            if ((!$qmMap->ladder_id || $qmMap->ladder_id == 0) && $qmMap->map_pool_id) {
                $mapPool = \App\Models\MapPool::find($qmMap->map_pool_id);
                if ($mapPool && $mapPool->ladder_id) {
                    $qmMap->ladder_id = $mapPool->ladder_id;
                    \Illuminate\Support\Facades\Log::warning('QmMap auto-set ladder_id from mapPool (defensive fallback)', [
                        'qm_map_id' => $qmMap->id,
                        'map_pool_id' => $qmMap->map_pool_id,
                        'ladder_id' => $qmMap->ladder_id,
                    ]);
                }
            }
        });
    }

    protected $fillable = [
        'ladder_id',
        'map_pool_id',
        'map_id',
        'valid',
        'description',
        'allowed_sides',
        'team1_spawn_order',
        'team2_spawn_order',
    ];

    //
    public function qmMatches()
    {
        return $this->hasMany(QmMatch::class);
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function mapPool()
    {
        return $this->belongsTo(MapPool::class, 'map_pool_id');
    }

    public function scopeValid($query)
    {
        return $query->where('valid', true);
    }

    public static function findMapsByLadder($id)
    {
        $ladder = Ladder::find($id);

        if (!$ladder) {
            \Illuminate\Support\Facades\Log::error('QmMap::findMapsByLadder - Ladder not found', ['ladder_id' => $id]);
            return collect();
        }

        if (!$ladder->mapPool) {
            \Illuminate\Support\Facades\Log::error('QmMap::findMapsByLadder - Ladder has no mapPool', [
                'ladder_id' => $id,
                'ladder_abbreviation' => $ladder->abbreviation,
                'ladder_name' => $ladder->name
            ]);
            return collect();
        }

        $qmMaps = $ladder->mapPool->maps;

        // Safety check: Filter out any remaining orphan maps (ladder_id mismatch)
        // Data should be clean now, but keep as defensive check
        $qmMaps = $qmMaps->filter(function ($qmMap) use ($id) {
            if ($qmMap->ladder_id != $id) {
                \Illuminate\Support\Facades\Log::warning('QmMap in pool has wrong ladder_id', [
                    'qm_map_id' => $qmMap->id,
                    'qm_map_ladder_id' => $qmMap->ladder_id,
                    'expected_ladder_id' => $id,
                ]);
                return false;
            }
            return true;
        });

        return $qmMaps->map(function ($qmMap)
        {
            $qmMap["hash"] = $qmMap->map->hash;
            $qmMap->map['image_url'] = asset($qmMap->map->image_path);
            $qmMap["allowed_sides"] = array_map('intval', explode(',', $qmMap->allowed_sides));

            return $qmMap;
        });
    }

    protected $_map_side_array = null;
    public function sides_array()
    {
        if ($this->_map_side_array === null)
        {
            $this->_map_side_array = explode(',', $this->allowed_sides);
        }
        return $this->_map_side_array;
    }

    public function spawnOptionValues()
    {
        return $this->hasMany(SpawnOptionValue::class);
    }

    public function qmMapPicks()
    {
        return $this->hasMany(QmMapPick::class);
    }
}
