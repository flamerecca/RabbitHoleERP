<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseTransferItem>
 */
class WarehouseTransferItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_transfer_id' => WarehouseTransfer::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
        ];
    }
}
