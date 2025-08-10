<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QmMatch extends Model
{

    use HasFactory;

    protected $fillable = [
        'ladder_id',
        'qm_map_id',
        'seed',
        'tier',
        'stats_teams_elo_diff',
        'stats_elo_gap_sum',
        'stats_match_ranking',
    ];

    public function players()
    {
        return $this->hasMany(QmMatchPlayer::class);
    }

    public function game()
    {
        return $this->hasOne(Game::class);
    }

    public function map()
    {
        return $this->belongsTo(QmMap::class, 'qm_map_id');
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class);
    }

    public function states()
    {
        return $this->hasMany(QmMatchState::class);
    }

    public function qmConnectionStats()
    {
        return $this->hasMany(QmConnectionStats::class);
    }

    public function findQmPlayerByPlayerId(int $playerId)
    {
        return $this->players->where('player_id', '=', $playerId)->first(); // returns a qm_match_player
    }

    public function observers()
    {
        return $this->hasMany(QmMatchPlayer::class)->where('is_observer', 1);
    }
}
