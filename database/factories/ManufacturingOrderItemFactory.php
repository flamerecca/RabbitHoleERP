<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ManufacturingOrder;
use App\Models\ManufacturingOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManufacturingOrderItem>
 */
class ManufacturingOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'manufacturing_order_id' => ManufacturingOrder::factory(),
            'component_product_id' => fn (array $attributes) => Product::factory()->create([
                'team_id' => ManufacturingOrder::query()->whereKey($attributes['manufacturing_order_id'])->value('team_id'),
            ])->id,
            'quantity' => 1,
        ];
    }
}
