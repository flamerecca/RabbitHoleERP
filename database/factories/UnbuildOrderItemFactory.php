<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\UnbuildOrder;
use App\Models\UnbuildOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnbuildOrderItem>
 */
class UnbuildOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unbuild_order_id' => UnbuildOrder::factory(),
            'component_product_id' => fn (array $attributes) => Product::factory()->create([
                'team_id' => UnbuildOrder::query()->whereKey($attributes['unbuild_order_id'])->value('team_id'),
            ])->id,
            'quantity' => 1,
        ];
    }
}
