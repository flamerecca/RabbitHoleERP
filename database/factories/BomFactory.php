<?php

namespace Database\Factories;

use App\Models\Bom;
use App\Models\Product;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bom>
 */
class BomFactory extends Factory
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
            'product_id' => fn (array $attributes) => Product::factory()->create(['team_id' => $attributes['team_id']])->id,
            'quantity' => 1,
            'is_active' => true,
        ];
    }
}
