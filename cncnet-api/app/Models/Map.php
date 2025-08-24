<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Map extends Model
{
    use LogsActivity, HasFactory;

    protected static $logAttributes = [
        'name', 'hash', 'image_path', 'description'
    ];
    protected static $logName = 'Map';
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected $table = 'maps';
    protected $fillable = ['name', 'hash', 'ladder_id', 'spawn_count'];
    public $timestamps = false;

    public function qmMaps()
    {
        return $this->hasMany(QmMap::class);
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function mapHeaders()
    {
        return $this->hasOne(MapHeader::class);
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
