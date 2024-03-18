<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QmMap extends Model {

    use HasFactory;

    protected $fillable = [
        'ladder_id',
        'map_pool_id',
        'map_id',
        'valid',
        'description',
        'allowed_sides',
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

    public function scopeValid($query)
    {
        return $query->where('valid', true);
    }

    public static function findMapsByLadder($id)
    {
        $ladder = Ladder::find($id);
        $qmMaps = $ladder->mapPool->maps;

        return $qmMaps->map( function($qmMap)
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
}
