<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockCostLayer;
use App\Models\StockMovement;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockCostLayer>
 */
class StockCostLayerFactory extends Factory
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
            'stock_movement_id' => StockMovement::factory(),
            'quantity' => 10,
            'unit_cost' => 10,
            'remaining_quantity' => fn (array $attributes) => $attributes['quantity'],
        ];
    }
}
