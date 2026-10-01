<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'unit_id' => fn (array $attributes) => Product::query()->whereKey($attributes['product_id'])->value('purchase_unit_id'),
            'quantity' => 10,
            'unit_price' => 100,
            'tax_rate_id' => null,
            'received_quantity' => 0,
        ];
    }
}
