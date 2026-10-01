<?php

namespace Database\Factories;

use App\Models\StockCostLayer;
use App\Models\StockCostLayerConsumption;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockCostLayerConsumption>
 */
class StockCostLayerConsumptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_cost_layer_id' => StockCostLayer::factory(),
            'stock_movement_id' => StockMovement::factory(),
            'quantity' => 1,
            'unit_cost' => 10,
        ];
    }
}
