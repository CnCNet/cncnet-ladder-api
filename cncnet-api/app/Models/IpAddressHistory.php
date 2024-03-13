<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpAddressHistory extends Model
{

    //

    public static function addHistory($user_id, $ip_address_id)
    {
        if ($user_id === null || $ip_address_id === null)
            return;

        $ipH = IpAddressHistory::where('user_id', '=', $user_id)->where('ip_address_id', '=', $ip_address_id)->first();
        if ($ipH === null)
        {
            $ipH = new IpAddressHistory;
            $ipH->user_id = $user_id;
            $ipH->ip_address_id = $ip_address_id;
        }
        $ipH->touch();
        $ipH->save();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ipAddress()
    {
        return $this->belongsTo(IpAddress::class);
    }
}
