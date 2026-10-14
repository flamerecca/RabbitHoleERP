<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Customer;
use App\Models\SalesReturn;
use App\Models\Shipment;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesReturn>
 */
class SalesReturnFactory extends Factory
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
            'shipment_id' => Shipment::factory(),
            'warehouse_id' => Warehouse::factory(),
            'return_no' => fake()->unique()->numerify('SR######/#####'),
            'return_date' => now()->toDateString(),
            'status' => DocumentStatus::Draft,
            'reason' => '商品瑕疵',
            'total_amount' => 0,
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
