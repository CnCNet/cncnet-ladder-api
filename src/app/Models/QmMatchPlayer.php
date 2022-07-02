<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmMatchPlayer extends Model
{

    //
    public function qmMatch()
    {
        return $this->belongsTo('App\Models\QmMatch');
    }

    public function player()
    {
        return $this->belongsTo('App\Models\Player');
    }

    public function ladder()
    {
        return $this->belongsTo('App\Models\Ladder');
    }

    protected $_map_side_array = null;
    public function map_side_array()
    {
        if ($this->_map_side_array === null)
        {
            $this->_map_side_array = explode(',', $this->mapSides->value);
        }
        return $this->_map_side_array;
    }

    public function qEntry()
    {
        return $this->hasOne('App\Models\QmQueueEntry');
    }

    public function ipAddress()
    {
        return $this->belongsTo('App\Models\IpAddress');
    }

    public function ipv6Address()
    {
        return $this->belongsTo('App\Models\IpAddress', 'ipv6_address');
    }

    public function lanAddress()
    {
        return $this->belongsTo('App\Models\IpAddress', 'lan_ip');
    }

    public function version()
    {
        return $this->belongsTo('App\Models\PlayerDataString', 'version_id');
    }

    public function platform()
    {
        return $this->belongsTo('App\Models\PlayerDataString', 'platform_id');
    }

    public function ddraw()
    {
        return $this->belongsTo('App\Models\PlayerDataString', 'ddraw_id');
    }

    public function mapSides()
    {
        return $this->belongsTo('App\Models\MapSideString', 'map_sides_id');
    }
}
