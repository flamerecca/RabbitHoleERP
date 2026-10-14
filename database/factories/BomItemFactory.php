<?php

namespace Database\Factories;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BomItem>
 */
class BomItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bom_id' => Bom::factory(),
            'component_product_id' => fn (array $attributes) => Product::factory()->create([
                'team_id' => Bom::query()->whereKey($attributes['bom_id'])->value('team_id'),
            ])->id,
            'quantity' => 1,
            'unit_id' => fn (array $attributes) => Product::query()->whereKey($attributes['component_product_id'])->value('unit_id'),
        ];
    }
}
