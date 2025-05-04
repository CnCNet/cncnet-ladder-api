<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IrcBan extends Model
{
    protected $connection = "irc";

    protected $casts = [
        'expires_at' => 'datetime',
        'ban_original_expiry' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(IrcBanLog::class, 'ban_id');
    }
}
