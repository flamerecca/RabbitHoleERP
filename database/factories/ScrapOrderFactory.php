<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\ScrapOrder;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScrapOrder>
 */
class ScrapOrderFactory extends Factory
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
            'warehouse_id' => Warehouse::factory(),
            'scrap_no' => fake()->unique()->numerify('SC######/#####'),
            'status' => DocumentStatus::Draft,
            'reason' => '過期報廢',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the document has been confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Confirmed,
        ]);
    }

    /**
     * Indicate that the document has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Cancelled,
        ]);
    }
}
