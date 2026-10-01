<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SalesOrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentItem>
 */
class ShipmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'sales_order_item_id' => SalesOrderItem::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
        ];
    }
}
