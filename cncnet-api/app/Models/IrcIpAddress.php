<?php

namespace App\Models;

use BadMethodCallException;
use \Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\InvalidCastException;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use MaxMind\Db\Reader;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use MaxMind\Db\Reader\InvalidDatabaseException;

class IrcIpAddress extends Model
{
    protected $connection = "irc";

    /**
     * 
     * @param string $ipAddress 
     */
    public static function findByIP(string $ipAddress)
    {
        if ($ipAddress === null || $ipAddress == '')
            return null;

        $ip = IrcIpAddress::where('address', '=', $ipAddress)->first();
        if ($ip === null)
        {
            $reader = new Reader(config('database.mmdb.file'));
            $mmData = $reader->get($ipAddress);

            $ip = new IrcIpAddress();
            $ip->address = $ipAddress;
            $ip->country = "unknown";
            $ip->city = "unknown";

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
        return IrcIpAddress::findByIP($address)->id;
    }

    public function ipHistory()
    {
        return $this->hasMany(IrcIpAddressHistory::class);
    }
}
