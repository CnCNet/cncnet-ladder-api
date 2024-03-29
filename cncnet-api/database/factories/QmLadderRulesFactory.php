<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QmLadderRules>
 */
class QmLadderRulesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_count' => 2,
            'map_vetoes' => 0,
            'max_difference' => 498,
            'all_sides' => '0,1,2,3,4,5,6,7,8,9',
            'allowed_sides' => '0,1,2',
            'bail_time' => 0,
            'bail_fps' => 0,
            'tier2_rating' => 1200,
            'rating_per_second' => 0.75,
            'max_points_difference' => 400,
            'points_per_second' => 1,
            'use_elo_points' => 1,
            'wol_k' => 64,
            'show_map_preview' => 1,
            'reduce_map_repeats' => 5,
            'use_ranked_map_picker' => 0,
        ];
    }
}
