<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QmLadderRules extends Model
{

    protected $fillable = [
        'ladder_id', 'player_count', 'map_vetoes', 'max_difference', 'all_sides',
        'allowed_sides', 'bail_time', 'bail_fps', 'tier2_rating', 'rating_per_second',
        'max_points_difference', 'points_per_second', 'use_elo_points', 'wol_k',
        'show_map_preview', 'reduce_map_repeats', 'use_ranked_map_picker'
    ];

    public static function newDefault($ladderId)
    {
        $rules = new QmLadderRules;

        $rules->ladder_id       = $ladderId;
        $rules->player_count    = 2;
        $rules->map_vetoes      = 1;
        $rules->max_difference  = 200;
        $rules->all_sides       = "";
        $rules->allowed_sides   = "";
        $rules->bail_time       = 30;
        $rules->bail_fps        = 30;
        $rules->tier2_rating    = 0;
        $rules->rating_per_second = 0.75;
        $rules->max_points_difference = 400;
        $rules->points_per_second = 0.5;
        $rules->use_elo_points    = true;
        $rules->wol_k             = 64;
        $rules->show_map_preview = true;
        $rules->use_ranked_map_picker = false;
        $rules->reduce_map_repeats = 0; //number of recent maps to exclude from next played game

        return $rules;
    }

    public function getMatchAIAfterSeconds()
    {
        return $this->match_ai_after_seconds;
    }

    public function ladder()
    {
        return $this->belongsTo('\App\Ladder');
    }

    // Delete Me
    public function mapPool()
    {
        return $this->ladder->mapPool;
    }

    public function mapPools()
    {
        return $this->hasMany('\App\MapPool');
    }

    public function all_sides()
    {
        return explode(',', $this->id ? $this->all_sides : "");
    }

    public function allowed_sides()
    {
        return explode(',', $this->id ? $this->allowed_sides : "");
    }

    // Delete Me
    public function spawnOptionValues()
    {
        return $this->hasMany('App\SpawnOptionValue');
    }

}

