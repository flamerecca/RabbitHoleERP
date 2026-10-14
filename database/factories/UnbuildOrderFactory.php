<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Bom;
use App\Models\Product;
use App\Models\Team;
use App\Models\UnbuildOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnbuildOrder>
 */
class UnbuildOrderFactory extends Factory
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
            'unbuild_no' => fake()->unique()->numerify('UB######/#####'),
            'warehouse_id' => fn (array $attributes) => Warehouse::factory()->create(['team_id' => $attributes['team_id']])->id,
            'product_id' => fn (array $attributes) => Product::factory()->create(['team_id' => $attributes['team_id']])->id,
            'bom_id' => fn (array $attributes) => Bom::factory()->create(['team_id' => $attributes['team_id'], 'product_id' => $attributes['product_id']])->id,
            'quantity' => 1,
            'status' => DocumentStatus::Draft,
            'reason' => '客訴退回',
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
