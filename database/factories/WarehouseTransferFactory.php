<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseTransfer>
 */
class WarehouseTransferFactory extends Factory
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
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id' => Warehouse::factory(),
            'transfer_no' => fake()->unique()->numerify('WT######/#####'),
            'status' => DocumentStatus::Draft,
            'transfer_date' => now()->toDateString(),
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
