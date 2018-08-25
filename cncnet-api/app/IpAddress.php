<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class IpAddress extends Model {

	//

    public static function findByIP($address)
    {
        $ip = IpAddress::where('address', '=', $address)->first();
        if ($ip === null)
        {
            $ip = new IpAddress;
            $ip->address = $address;
            $ip->save();
        }
        return $ip;
    }

    public static function getID($address)
    {
        return IpAddress::findByIP($address)->id;
    }

    public function users()
    {
        return $this->hasMany('App\User', 'ip_address_id', 'id');
    }

    public function bans()
    {
        return $this->hasMany('App\Ban', 'ip_address_id', 'id');
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
}
