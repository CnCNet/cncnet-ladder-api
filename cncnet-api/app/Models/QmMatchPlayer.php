<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmMatchPlayer extends Model
{
    protected $_map_side_array = null;

    public function map_side_array()
    {
        if ($this->_map_side_array === null)
        {
            $this->_map_side_array = explode(',', $this->mapSides->value);
        }
        return $this->_map_side_array;
    }

    public function isObserver()
    {
        return $this->is_observer;
    }

    # Relationships
    public function clan()
    {
        return $this->belongsTo(Clan::class);
    }

    public function qmMatch()
    {
        return $this->belongsTo(QmMatch::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function qEntry()
    {
        return $this->hasOne(QmQueueEntry::class);
    }

    public function ipAddress()
    {
        return $this->belongsTo(IpAddress::class);
    }

    public function ipv6Address()
    {
        return $this->belongsTo(IpAddress::class, 'ipv6_address');
    }

    public function lanAddress()
    {
        return $this->belongsTo(IpAddress::class, 'lan_ip');
    }

    public function version()
    {
        return $this->belongsTo(PlayerDataString::class, 'version_id');
    }

    public function platform()
    {
        return $this->belongsTo(PlayerDataString::class, 'platform_id');
    }

    public function ddraw()
    {
        return $this->belongsTo(PlayerDataString::class, 'ddraw_id');
    }

    public function mapSides()
    {
        return $this->belongsTo(MapSideString::class, 'map_sides_id');
    }
}
