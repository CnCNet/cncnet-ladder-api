<?php

namespace Database\Factories;

use App\Models\GameObjectSchema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ladder>
 */
class LadderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'abbreviation' => $this->faker->randomLetter() . $this->faker->randomLetter(),
            'game' => 'yr',
            'clans_allowed' => false,
            'game_object_schema_id' => GameObjectSchema::factory(),
            'private' => false,
        ];
    }
}
