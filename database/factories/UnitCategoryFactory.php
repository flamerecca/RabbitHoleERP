<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\UnitCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitCategory>
 */
class UnitCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
