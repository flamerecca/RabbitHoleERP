<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductDiscount>
 */
class ProductDiscountFactory extends Factory
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
            'product_id' => Product::factory(),
            'discount_rate' => 10,
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-31',
            'is_active' => true,
        ];
    }
}
