<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\Currency;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
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
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'currency_id' => Currency::factory(),
            'exchange_rate' => 1,
            'order_no' => fake()->unique()->numerify('PO######/#####'),
            'status' => PurchaseOrderStatus::Draft,
            'order_date' => now()->toDateString(),
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the purchase order has been confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Confirmed,
        ]);
    }

    /**
     * Indicate that the purchase order has been partially received.
     */
    public function partiallyReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::PartiallyReceived,
        ]);
    }

    /**
     * Indicate that the purchase order has been fully received.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Received,
        ]);
    }

    /**
     * Indicate that the purchase order has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Cancelled,
        ]);
    }
}
