<?php

namespace Database\Factories;

use App\Enums\SalesOrderStatus;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
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
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'currency_id' => Currency::factory(),
            'exchange_rate' => 1,
            'order_no' => fake()->unique()->numerify('SO######/#####'),
            'status' => SalesOrderStatus::Draft,
            'order_date' => now()->toDateString(),
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the sales order has been confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SalesOrderStatus::Confirmed,
        ]);
    }

    /**
     * Indicate that the sales order has been partially shipped.
     */
    public function partiallyShipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SalesOrderStatus::PartiallyShipped,
        ]);
    }

    /**
     * Indicate that the sales order has been fully shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SalesOrderStatus::Shipped,
        ]);
    }

    /**
     * Indicate that the sales order has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SalesOrderStatus::Cancelled,
        ]);
    }
}
