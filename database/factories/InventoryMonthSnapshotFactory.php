<?php

namespace Database\Factories;

use App\Models\InventoryMonthSnapshot;
use App\Models\Product;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMonthSnapshot>
 */
class InventoryMonthSnapshotFactory extends Factory
{
    /**
     * Define the model's default state, a month with ten units in stock at five each.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'period' => '2026-08',
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'opening_quantity' => 0,
            'opening_amount' => 0,
            'in_quantity' => 10,
            'in_amount' => 50,
            'out_quantity' => 0,
            'out_amount' => 0,
            'closing_quantity' => 10,
            'closing_amount' => 50,
            'average_cost' => 5,
            'closed_by' => null,
            'closed_at' => now(),
        ];
    }
}
