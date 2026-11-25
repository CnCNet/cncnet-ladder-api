<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameChatFlag extends Model
{
    public const OPEN = 'open';
    public const REVIEWED = 'reviewed';
    public const DISMISSED = 'dismissed';

    // ChatFilter's reasons as staff see them.
    public const REASONS = ['word' => 'Word', 'burst' => 'Spam', 'repeat' => 'Repeated'];

    protected $table = 'game_chat_flags';

    protected $fillable = [
        'game_id',
        'ladder_id',
        'reason',
        'house',
        'name',
        'frame',
        'seconds',
        'detail',
        'text',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** What identifies a flag across re-scans of the same game. */
    public function key(): string
    {
        return "{$this->reason}|{$this->house}|{$this->frame}";
    }
}
