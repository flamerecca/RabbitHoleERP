<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ScrapOrder;
use App\Models\ScrapOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScrapOrderItem>
 */
class ScrapOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scrap_order_id' => ScrapOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
        ];
    }
}
