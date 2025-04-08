<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IrcIpAddressHistory extends Model
{
    protected $connection = "irc";
    protected $table = "irc_ip_addresses_histories";

    /**
     * 
     * @param int $ircUserId 
     * @param int $ircIpAddressId 
     * @return void 
     */
    public static function addHistory(int $ircUserId, int $ircIpAddressId)
    {
        if ($ircUserId === null || $ircIpAddressId === null)
            return;

        $ircIpAddressHistory = IrcIpAddressHistory::where('irc_user_id', '=', $ircUserId)->where('ip_address_id', '=', $ircIpAddressId)->first();
        if ($ircIpAddressHistory === null)
        {
            $ircIpAddressHistory = new IrcIpAddressHistory;
            $ircIpAddressHistory->irc_user_id = $ircUserId;
            $ircIpAddressHistory->ip_address_id = $ircIpAddressId;
        }
        $ircIpAddressHistory->touch();
        $ircIpAddressHistory->save();
    }

    public function ircUser()
    {
        return $this->belongsTo(IrcUser::class);
    }

    public function ipAddress()
    {
        return $this->belongsTo(IrcIpAddress::class);
    }
}
