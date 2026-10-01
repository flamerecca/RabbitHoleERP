<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Unit;
use App\Models\UnitCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
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
            'category_id' => UnitCategory::factory(),
            'name' => fake()->randomElement(['個', '箱', '公斤', '包']),
            'code' => fake()->unique()->bothify('U-###'),
            'ratio' => 1,
            'is_reference' => true,
            'is_active' => true,
        ];
    }
}
