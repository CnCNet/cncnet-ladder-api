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
        $colors = config('game_colors.ra2_player_colors', []);
        $defaultColor = config('game_colors.default_color', '#FFFFFF');

        return $colors[$colorId] ?? $defaultColor;
    }
}
