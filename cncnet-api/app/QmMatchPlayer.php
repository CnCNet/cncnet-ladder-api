<?php

namespace App;

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

    # Relationships
    public function clan()
    {
        return $this->belongsTo('App\Clan');
    }

    public function qmMatch()
    {
        return $this->belongsTo('App\QmMatch');
    }

    public function player()
    {
        return $this->belongsTo('App\Player');
    }

    public function ladder()
    {
        return $this->belongsTo('App\Ladder');
    }

    public function qEntry()
    {
        return $this->hasOne('App\QmQueueEntry');
    }

    public function ipAddress()
    {
        return $this->belongsTo('App\IpAddress');
    }

    public function ipv6Address()
    {
        return $this->belongsTo('App\IpAddress', 'ipv6_address');
    }

    public function lanAddress()
    {
        return $this->belongsTo('App\IpAddress', 'lan_ip');
    }

    public function version()
    {
        return $this->belongsTo('App\PlayerDataString', 'version_id');
    }

    public function platform()
    {
        return $this->belongsTo('App\PlayerDataString', 'platform_id');
    }

    public function ddraw()
    {
        return $this->belongsTo('App\PlayerDataString', 'ddraw_id');
    }

    public function mapSides()
    {
        return $this->belongsTo('App\MapSideString', 'map_sides_id');
    }
}
