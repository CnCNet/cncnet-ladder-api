<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QmCanceledMatch extends Model {

    protected $fillable = [
        'qm_match_id',
        'player_id',
        'ladder_id',
        'map_name',
        'canceled_by_usernames',
        'affected_player_usernames',
        'player_data',
        'reason',
    ];

    protected $casts = [
        'player_data' => 'array',
    ];

    public function qmMatch()
    {
        return $this->belongsTo(QmMatch::class, 'qm_match_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function ladder()
    {
        return $this->belongsTo(Ladder::class, 'ladder_id');
    }

    /**
     * Get CSS color for a C&C player color ID
     * Based on Red Alert 2 / Yuri's Revenge color scheme
     */
    public static function getColorForId($colorId)
    {
        $colors = [
            0 => '#FFD700', // Yellow/Gold
            1 => '#FF0000', // Red
            2 => '#0080FF', // Blue
            3 => '#00FF00', // Green
            4 => '#FF8000', // Orange
            5 => '#00FFFF', // Cyan/Teal
            6 => '#FF00FF', // Purple/Magenta
            7 => '#FFB6C1', // Pink/Light Pink
        ];

        return $colors[$colorId] ?? '#FFFFFF'; // Default to white if unknown
    }
}
