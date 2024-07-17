<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IrcWarning extends Model
{
    protected $connection = "irc";

    protected $casts = [
        'expires_at' => 'datetime',
        'ban_original_expiry' => 'datetime',
    ];
}
