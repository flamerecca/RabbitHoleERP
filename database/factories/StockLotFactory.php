<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockLot;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockLot>
 */
class StockLotFactory extends Factory
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
            'product_id' => Product::factory()->lotTracked(),
            'lot_no' => fake()->unique()->bothify('LOT-########-##'),
        ];
    }
}
