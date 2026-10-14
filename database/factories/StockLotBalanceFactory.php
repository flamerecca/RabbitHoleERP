<?php

namespace Database\Factories;

use App\Models\StockLot;
use App\Models\StockLotBalance;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockLotBalance>
 */
class StockLotBalanceFactory extends Factory
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
            'stock_lot_id' => StockLot::factory(),
            'product_id' => fn (array $attributes) => StockLot::query()->whereKey($attributes['stock_lot_id'])->value('product_id'),
            'quantity_on_hand' => 0,
        ];
    }
}
