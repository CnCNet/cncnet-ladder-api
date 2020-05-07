<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    protected $table = 'maps';
    protected $fillable = ['name', 'hash'];
    public $timestamps = false;

    public function qmMaps()
    {
        return $this->hasMany('App\QmMap');
    }

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }
}
