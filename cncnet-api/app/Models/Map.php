<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use \Spatie\Activitylog\LogOptions;

class Map extends Model
{
    use LogsActivity, HasFactory;

    protected $table = 'maps';
    protected $fillable = ['name', 'hash', 'ladder_id', 'spawn_count'];
    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'hash', 'image_path', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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
