<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTakeItem>
 */
class StockTakeItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_take_id' => StockTake::factory(),
            'product_id' => Product::factory(),
            'system_quantity' => 10,
            'counted_quantity' => 10,
            'difference' => 0,
        ];
    }
}
