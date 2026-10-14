<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
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
            'sales_order_id' => SalesOrder::factory(),
            'warehouse_id' => Warehouse::factory(),
            'shipment_no' => fake()->unique()->numerify('SH######/#####'),
            'shipped_date' => now()->toDateString(),
            'status' => DocumentStatus::Draft,
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
