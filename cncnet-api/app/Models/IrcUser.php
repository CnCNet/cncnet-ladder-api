<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IrcUser extends Model
{
    protected $connection = "irc";

    protected $fillable = ['ident', 'username', 'host', 'updated_at',];

    public function ipHistory()
    {
        return $this->hasMany(IpAddressHistory::class);
    }

    public function bans()
    {
        return $this->hasMany(IrcBan::class);
    }
}
