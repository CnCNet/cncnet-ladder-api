<?php namespace App;

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
        return $this->belongsTo('App\Ladder');
    }

    public function mapHeaders()
    {
        return $this->hasOne('App\MapHeader');
    }

    public function getMapPath()
    {
        if ($this->image_path)
        {
            if (config("app.env") !== "production")
            {
                return "https://ladder.cncnet.org/" . $this->image_path;
            }
            return asset($this->image_path, true);
        }
        return null;
    }
}
