<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IrcBanLog extends Model
{
    const ACTION_EXPIRED = "Expire";
    const ACTION_UPDATED = "Updated";
    const ACTION_CREATED = "Created";

    protected $connection = "irc";
}
