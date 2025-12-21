<?php

namespace App\Models;

use \Exception;
use Illuminate\Database\Eloquent\Model;
use MaxMind\Db\Reader;

class IpAddress extends Model
{

    //

    public static function findByIP($address)
    {
        if ($address === null || $address == '')
            return null;

        $ip = IpAddress::where('address', '=', $address)->first();
        if ($ip === null)
        {
            $reader = new Reader(config('database.mmdb.file'));
            $mmData = $reader->get($address);

            $ip = new IpAddress;
            $ip->address = $address;
            if ($mmData !== null)
            {
                try
                {
                    if (array_key_exists("country", $mmData))
                        $ip->country = $mmData["country"]["iso_code"];

                    if (array_key_exists("city", $mmData))
                        $ip->city = $mmData["city"]["names"]["en"];
                }
                catch (Exception $e)
                {
                    error_log($e->getMessage());
                }
            }
            $ip->save();
            $reader->close();
        }
        return $ip;
    }

    public static function getID($address)
    {
        return IpAddress::findByIP($address)->id;
    }

    public function users()
    {
        return $this->hasMany(User::class, 'ip_address_id', 'id');
    }

    public function bans()
    {
        return $this->hasMany(Ban::class, 'ip_address_id', 'id');
    }

    public function getBan($start = false)
    {
        $ret = null;
        foreach ($this->users as $user)
        {
            $ret = $user->getBan($start);
        }
        return $ret;
    }

    public function ipHistory()
    {
        return $this->hasMany(IpAddressHistory::class);
    }
}
