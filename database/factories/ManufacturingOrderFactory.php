<?php

namespace Database\Factories;

use App\Enums\ManufacturingOrderStatus;
use App\Models\Bom;
use App\Models\Product;
use App\Models\Team;
use App\Models\ManufacturingOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManufacturingOrder>
 */
class ManufacturingOrderFactory extends Factory
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
            'mo_no' => fake()->unique()->numerify('MO######/#####'),
            'product_id' => fn (array $attributes) => Product::factory()->create(['team_id' => $attributes['team_id']])->id,
            'bom_id' => fn (array $attributes) => Bom::factory()->create(['team_id' => $attributes['team_id'], 'product_id' => $attributes['product_id']])->id,
            'warehouse_id' => fn (array $attributes) => Warehouse::factory()->create(['team_id' => $attributes['team_id']])->id,
            'quantity' => 1,
            'status' => ManufacturingOrderStatus::Draft,
            'planned_date' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the document has been confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ManufacturingOrderStatus::Confirmed,
        ]);
    }

    /**
     * Indicate that the order has been completed.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ManufacturingOrderStatus::Done,
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the document has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ManufacturingOrderStatus::Cancelled,
        ]);
    }
}
