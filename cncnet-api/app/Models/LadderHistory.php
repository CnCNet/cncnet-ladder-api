<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LadderHistory extends Model
{
    use HasFactory;

    protected $table = 'ladder_history';
    protected $casts = [
        'starts' => 'datetime',
        'ends' => 'datetime'
    ];

    protected $fillable = [
        'ladder_id',
        'starts',
        'ends',
        'short',
    ];

    public $timestamps = false;

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function hasEnded()
    {
        return $this->ends < Carbon::now();
    }

    public function queued_players() {
        return $this->hasMany(QmQueueEntry::class)
            ->whereHas('qmPlayer', function($q) {
                $q->whereNull('qm_match_id');
            });
    }

    public function queued_players_pros() {
        return QmQueueEntry::join('qm_match_players', 'qm_match_players.id', '=', 'qm_queue_entries.qm_match_player_id')
        ->join('players', 'players.id', '=', 'qm_match_players.player_id')
        ->join('users', 'users.id', '=', 'players.user_id')
        ->join('user_pros', 'user_pros.user_id', '=', 'users.id')
        ->where('ladder_history_id', $this->id)
        ->whereNull('qm_match_id')
        ->count();
    }
}
