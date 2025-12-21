<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LadderHistory>
 */
class LadderHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'starts' => today()->startOfMonth(),
            'ends' => today()->endOfMonth(),
            'short' => today()->startOfMonth()->format('n-Y')
        ];
    }
}
