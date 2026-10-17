<?php

namespace Database\Factories;

use App\Models\PosOrderLine;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosOrderLine>
 */
class PosOrderLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pos_order_id' => PosOrder::factory(),
            'product_id' => fn (array $attributes) => Product::factory()->recycle(PosOrder::query()->findOrFail($attributes['pos_order_id'])->team)->create()->id,
            'quantity' => 1,
            'unit_price' => 105,
            'discount_rate' => 0,
            'tax_rate_id' => fn (array $attributes) => TaxRate::factory()->create(['team_id' => PosOrder::query()->whereKey($attributes['pos_order_id'])->value('team_id')])->id,
            'tax_rate' => 0.05,
            'line_total' => 105,
            'line_tax' => 5,
        ];
    }
}
