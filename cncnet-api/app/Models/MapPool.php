<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapPool extends Model {

    use HasFactory;

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
}
