<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapTier extends Model
{
    public $timestamps = false;

    
    protected $fillable = [
        'name',
        'map_pool_id',
        'max_vetoes',
        'tier'
    ];

    public function mapPool()
    {
        return $this->belongsTo(MapPool::class);
    }
}
