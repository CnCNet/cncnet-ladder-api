<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatFilterSettings extends Model
{
    protected $table = 'chat_filter_settings';

    protected $fillable = [
        'burst_messages',
        'burst_seconds',
        'repeat_messages',
        'repeat_seconds',
    ];

    protected $casts = [
        'burst_messages'  => 'integer',
        'burst_seconds'   => 'integer',
        'repeat_messages' => 'integer',
        'repeat_seconds'  => 'integer',
    ];

    // More than 6 lines from one sender in 10 seconds, or the same line 4 times in a minute: about one
    // game in a hundred across the November 2026 ladder replays.
    public const DEFAULTS = [
        'burst_messages'  => 7,
        'burst_seconds'   => 10,
        'repeat_messages' => 4,
        'repeat_seconds'  => 60,
    ];

    /** The one row, or the defaults while staff have not saved any. */
    public static function current(): self
    {
        return self::query()->first() ?? new self(self::DEFAULTS);
    }
}
