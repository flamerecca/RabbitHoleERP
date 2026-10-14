<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
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
            'type' => StockMovementType::AdjustmentIn,
            'quantity' => 1,
            'balance_after' => 1,
            'reference_type' => null,
            'reference_id' => null,
            'note' => '人工修正',
            'created_by' => User::factory(),
        ];
    }
}
