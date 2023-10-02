<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class MapTier extends Model
{
    public $timestamps = false;

    public function mapPool()
    {
        return $this->belongsTo('App\MapPool');
    }
}
