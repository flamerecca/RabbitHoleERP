<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockBalance>
 */
class StockBalanceFactory extends Factory
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
            'product_id' => Product::factory(),
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'average_cost' => 0,
        ];
    }
}
