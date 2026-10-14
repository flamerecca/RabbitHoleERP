<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrderItem>
 */
class SalesOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_id' => Product::factory(),
            'unit_id' => fn (array $attributes) => Product::query()->whereKey($attributes['product_id'])->value('unit_id'),
            'quantity' => 10,
            'unit_price' => 200,
            'tax_rate_id' => null,
            'shipped_quantity' => 0,
            'reserved_quantity' => 0,
        ];
    }
}
