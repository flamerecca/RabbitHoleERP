<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
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
            'name' => fake()->city().'倉',
            'code' => fake()->unique()->bothify('WH-###'),
            'address' => fake()->address(),
            'is_default' => false,
            'manager_email' => fake()->safeEmail(),
        ];
    }
}
