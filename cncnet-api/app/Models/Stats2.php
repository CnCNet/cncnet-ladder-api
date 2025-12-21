<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stats2 extends Model
{
    //
    protected $table = 'stats2';
    public $timestamps = false;
    public $gameStatsColumns = ['sid', 'col', 'cty', 'crd', 'hrv'];

    public function playerGameReport()
    {
        return $this->belongsTo(PlayerGameReport::Class);
    }

    public function gameObjectCounts()
    {
        return $this->hasMany(GameObjectCounts::Class, 'stats_id');
    }

    public function faction(Ladder $ladder)
    {
        $game = $ladder->game;

        $local_id = null;
        if ($game == 'yr')
        {
            $local_id = $this->cty;
        }
        else
        {
            $local_id = $this->sid;
        }

        if ($local_id === null) return "";

        if (!is_numeric($local_id))
        {
            // RA uses strings as side id's
            return strtolower($local_id);
        }

        $side = $ladder->sides->where('local_id', $local_id)->first();

        return $side !== null ? strtolower($side->name) : "";
    }

    public function country($side)
    {
        return Stats2::getCountryById($side);
    }

    public static function getCountryNameById($side)
    {
        if ($side === null)
        {
            return "";
        }

        switch ($side)
        {
            case 0:
                return "America";
            case 1:
                return "Korea";
            case 2:
                return "France";
            case 3:
                return "Germany";
            case 4:
                return "Great Britain";
            case 5:
                return "Libya";
            case 6:
                return "Iraq";
            case 7:
                return "Cuba";
            case 8:
                return "Russia";
            case 9:
                return "Yuri";
            default:
                return "";
        }
        return "";
    }

    public static function getCountryById($side)
    {
        if ($side === null)
        {
            return "";
        }

        switch ($side)
        {
            case 0:
                return "um";
            case 1:
                return "kr";
            case 2:
                return "fr";
            case 3:
                return "de";
            case 4:
                return "gb";
            case 5:
                return "ly";
            case 6:
                return "iq";
            case 7:
                return "cu";
            case 8:
                return "ru";
            case 9:
                return "yuri";
            default:
                return "";
        }
        return "";
    }

    public function colour($colour)
    {
        switch ($colour)
        {
            case 3:
                return "yellow";
            case 13:
                return "orange";
            case 11:
                return "red";
            case 15:
                return "pink";
            case 17:
                return "purple";
            case 21:
                return "blue";
            case 25:
                return "teal";
            case 29:
                return "green";
            default:
                return "";
        }

        return "random";
    }
}
