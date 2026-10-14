<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturn>
 */
class PurchaseReturnFactory extends Factory
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
            'goods_receipt_id' => GoodsReceipt::factory(),
            'warehouse_id' => Warehouse::factory(),
            'return_no' => fake()->unique()->numerify('PR######/#####'),
            'return_date' => now()->toDateString(),
            'status' => DocumentStatus::Draft,
            'reason' => '品質不良',
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
