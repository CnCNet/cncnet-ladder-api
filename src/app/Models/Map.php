<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    protected $table = 'maps';
    protected $fillable = ['name', 'hash', 'ladder_id'];
    public $timestamps = false;

    public function qmMaps()
    {
        return $this->hasMany('App\QmMap');
    }

    public function ladder()
    {
        return $this->belongsTo('App\Models\Ladder');
    }
}
