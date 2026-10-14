<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
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
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'position' => fake()->jobTitle(),
            'is_active' => true,
        ];
    }
}
